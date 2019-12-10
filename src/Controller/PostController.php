<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use App\Entity\Comment;
use App\Form\CommentType;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/forum")
 */
class PostController extends AbstractController
{
    
    /**
     * @Route("/", name="post_index", methods={"GET"})
     * @Route("/category/{cat}", name="post_index_cat", methods={"GET"})
     */
    public function index(Request $request, PaginatorInterface $paginator, PostRepository $postRepository, CategoryRepository $categoryRepository, $cat = null): Response
    {

        if($cat != null)
        {
         $catagorie = $categoryRepository->findOneBy(['name' => $cat]);
         $posts = $postRepository->findBy(['category' => $catagorie->getId()]);

         // Paginate the results of the query
         $allposts = $paginator->paginate(
            // Doctrine Query, not results
                $posts,
            // Define the page parameter
                $request->query->getInt('page', 1),
            // Items per page
                5
            );
        } else {
            $posts = $postRepository->findAll();
            $allposts = $paginator->paginate(
                // Doctrine Query, not results
                    $posts,
                // Define the page parameter
                    $request->query->getInt('page', 1),
                // Items per page
                    5
                );
        }
        return $this->render('post/index.html.twig', [
            'categories' => $categoryRepository->findAll(),
            'posts' => $allposts,
        ]);
        // return $this->render('post/index.html.twig', [
        //     'categories' => $categoryRepository->findAll(),

        //     'posts' => $postRepository->findAll(),
        // ]);
    }

    /**
     * @Route("/new", name="post_new", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_CUSTOMER')")
     */
    public function new(Request $request): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $user = $this->get('security.token_storage')->getToken()->getUser();
            $post->setUser($user);
            $entityManager->persist($post);
            $entityManager->flush();

            return $this->redirectToRoute('post_index');
        }

        return $this->render('post/new.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="post_show", methods={"GET"})
     */
    public function show(Request $request, PaginatorInterface $paginator,PostRepository $postRepository, CommentRepository $commentRepository, $id = null): Response
    {

        if($id != null)
        {
         $post = $postRepository->findOneBy(['id' => $id]);
         $comments = $commentRepository->findBy(['post' => $post->getId()]);
        // Paginate the results of the query
        $allcomments = $paginator->paginate(
        // Doctrine Query, not results
            $comments,
        // Define the page parameter
            $request->query->getInt('page', 1),
        // Items per page
            5
        );

        }
        return $this->render('post/show.html.twig', [
            'post' => $post,
            'comments' => $allcomments,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="post_edit", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_CUSTOMER')")
     */
    public function edit(Request $request, Post $post): Response
    {
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('post_index');
        }

        return $this->render('post/edit.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="post_delete", methods={"DELETE"})
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_CUSTOMER')")
     */
    public function delete(Request $request, Post $post): Response
    {
        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($post);
            $entityManager->flush();
        }

        return $this->redirectToRoute('post_index');
    }

    /**
     * @Route("/{id}/comment", name="post_comment", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_CUSTOMER')")
     */
    public function newComment(Request $request, PostRepository $postRepository, $id = null): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $user = $this->get('security.token_storage')->getToken()->getUser();
            $post = $postRepository->findOneBy(['id' => $id]);
            $comment->setUser($user);
            $comment->setPost($post);
            $entityManager->persist($comment);
            $entityManager->flush();

            return $this->redirectToRoute('post_show',[
                'id' => $id,
            ]);
        }

        return $this->render('comment/new.html.twig', [
            'comment' => $comment,
            'form' => $form->createView(),
        ]);
    }
}

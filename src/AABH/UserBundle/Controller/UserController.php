<?php

namespace AABH\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AABH\UserBundle\Entity\User;
use AABH\UserBundle\Form\UserType;


class UserController extends Controller
{
    public function indexAction(Request $request)
    {
        $ab=$this->getDoctrine()->getManager();
        
        $dql = "Select u FROM AABHUserBundle:User u";
        $users = $ab->createQuery($dql);
        
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $users, $request->query->getInt('page', 1),
            5
            );
        
        /*$users = $ab->getRepository('AABHUserBundle:User')->findall();
        
         $res = 'Lista de Usuarios: <br/>';
        
        foreach($users as $user){
            $res .= 'Usuario: ' .$user->getUsername() . ' - Email: ' .$user->getEmail(). '<br/>';
        }
        
        return new Response($res);*/
        
       // return $this->render('AABHUserBundle:User:index.html.twig', array('users'=>$users));
       return $this->render('AABHUserBundle:User:index.html.twig', array('pagination'=>$pagination));
    }
    
         public function viewAction($id){
        
        $repository = $this->getDoctrine()->getRepository('AABHUserBundle:User');
        
        $user = $repository->find($id);
        
        //return new Response('Usuario: ' . $user->getUsername(). ' - Email: ' .$user->getEmail());
        
        return  $this->render('AABHUserBundle:User:view.html.twig', array('users'=>$users));
    } 
    
    public function addAction(){
        
        /*Creamos una nueva instancia del objeto User */
        $user = new User();
        $form = $this->createCreateForm($user);
        
        return $this->render('AABHUserBundle:User:add.html.twig', array('form' => $form->createView()));
        
    }
    
    private function createCreateForm(User $entity){
        
        $form = $this->createForm(new UserType(), $entity, array(
                'action' => $this->generateUrl('aabh_user_create'),
                'method' => 'POST'
            ));
        
        return $form;
    }
    
    public function createAction(Request $request){
        $user = new User();
        $form = $this->createCreateForm($user);
        $form->handleRequest($request);
        
        if($form -> isValid()){
            $password = $form->get('password')->getData();
            $encoder = $this->container->get('security.password_encoder');
            $encoded = $encoder->encodePassword($user, $password);
            $user->setPassword($encoded);
            $ab = $this->getDoctrine()->getManager();
            $ab->persist($user);
            $ab->flush();
            
            $messa = $this->get('translator')->trans('The user has bee created.');
            
            $this->addFlash('mensaje',$messa);
            
            return $this->redirectToRoute('aabh_user_index');
        }
        
        return $this->render('AABHUserBundle:User:add.html.twig', array('form' => $form->createView()));
    }
    
    
   /* public function articulosAction($page)
    {
        return new Response('Este es mi articulo ' . $page );
    }*/
}

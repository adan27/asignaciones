<?php

namespace AABH\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;


class UserController extends Controller
{
    public function indexAction()
    {
        $ab=$this->getDoctrine()->getManager();
        
        $users = $ab->getRepository('AABHUserBundle:User')->findall();
        
        /* $res = 'Lista de Usuarios: <br/>';
        
        foreach($users as $user){
            $res .= 'Usuario: ' .$user->getUsername() . ' - Email: ' .$user->getEmail(). '<br/>';
        }
        
        return new Response($res);*/
        
        return $this->render('AABHUserBundle:User:index.html.twig', array('users'=>$users));
    }
    
    public function viewAction($id){
        
        $repository = $this->getDoctrine()->getRepository('AABHUserBundle:User');
        
        $user = $repository->find($id);
        
        //return new Response('Usuario: ' . $user->getUsername(). ' - Email: ' .$user->getEmail());
        
        return  $this->render('AABHUserBundle:User:view.html.twig', array('users'=>$users));
    }
   /* public function articulosAction($page)
    {
        return new Response('Este es mi articulo ' . $page );
    }*/
}

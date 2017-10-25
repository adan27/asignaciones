<?php

namespace AABH\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as  Assert;
use Symfony\Component\Form\FormError;
use AABH\UserBundle\Entity\User;
use AABH\UserBundle\Form\UserType;


class UserController extends Controller
{
    
    public function homeAction(){
        return $this->render('AABHUserBundle:User:home.html.twig');
    }
    
    public function indexAction(Request $request)
    {
        $searchQuery = $request->get('query');
        
        if(!empty($searchQuery)){
            $finder = $this->container->get('fos_elastica.finder.app.user');
            $users = $finder->createPaginatorAdapter($searchQuery);
        }else{
        
            $ab=$this->getDoctrine()->getManager();
            
            $dql = "Select u FROM AABHUserBundle:User u order by u.id desc";
            $users = $ab->createQuery($dql);
        }
        
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $users, $request->query->getInt('page', 1),
            5
            );
        
        $deleteFormAjax = $this-> createCustomForm(':USER_ID', 'DELETE', 'aabh_user_delete');
        
        /*$users = $ab->getRepository('AABHUserBundle:User')->findall();
        
         $res = 'Lista de Usuarios: <br/>';
        
        foreach($users as $user){
            $res .= 'Usuario: ' .$user->getUsername() . ' - Email: ' .$user->getEmail(). '<br/>';
        }
        
        return new Response($res);*/
        
       // return $this->render('AABHUserBundle:User:index.html.twig', array('users'=>$users));
       return $this->render('AABHUserBundle:User:index.html.twig', array('pagination'=>$pagination, 'delete_form_ajax' => $deleteFormAjax->createView()));
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
            
            $passwordConstraints = new Assert\NotBlank();
            $errorList = $this->get('validator')->validate($password, $passwordConstraints);
            
            if(count($errorList) == 0){
            
                $encoder = $this->container->get('security.password_encoder');
                $encoded = $encoder->encodePassword($user, $password);
                $user->setPassword($encoded);
                $ab = $this->getDoctrine()->getManager();
                $ab->persist($user);
                $ab->flush();
                
                $messa = $this->get('translator')->trans('The user has bee created.');
                
                $this->addFlash('mensaje',$messa);
                
                return $this->redirectToRoute('aabh_user_index');
            }else{
                $mensajeerror = new FormError($errorList[0]->getMessage());
                $form->get('password')->addError($mensajeerror);
            }
        }
        
        return $this->render('AABHUserBundle:User:add.html.twig', array('form' => $form->createView()));
    }
    
    public function editAction($id){
        
        $ab = $this->getDoctrine()->getManager();
        $user = $ab->getRepository('AABHUserBundle:User')->find($id);
        
        if(!$user){
            $mensaje = $this->get('translator')->trans('User not found.');
            throw $this->createNotFounException($mensaje);
        }
        
        $form = $this->createEditForm($user);
        
        return $this->render('AABHUserBundle:User:edit.html.twig', array('user' => $user, 'form' => $form->createView()));
    }
    
    private function createEditForm(User $entity){
        
        $form = $this->createForm(new UserType(), $entity, array('action' => $this->generateUrl('aabh_user_update', array('id'=> $entity->getId())), 'method' => 'PUT'));
        
        return $form;
    }
    
    public function updateAction($id, Request $request){
        $ab = $this->getDoctrine()->getManager();
        $user = $ab->getRepository('AABHUserBundle:User')->find($id);
        
        if(!$user){
            $mensaje = $this->get('translator')->trans('User not found.');
            throw $this->createNotFounException($mensaje);
        }
        
        $form = $this->createEditForm($user);
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid()){
            $password = $form->get('password')->getData();
            
            if(!empty($password)){
                $encoder = $this->container->get('security.password_encoder');
                $encoded = $encoder->encodePassword($user, $password);
                $user->setPassword($encoded);
            }else{
                $recoverPass = $this->recoverPass($id);
                $user->setPassword($recoverPass[0]['password']);
            }
            
            if($form->get('role')->getData() == 'ROLE_ADMIN'){
                $user->setIsActive(1);
            }
            
            $ab -> flush();
            $mensaje = $this->get('translator')->trans('The user has been modified.');
            $this->addFlash('mensaje', $mensaje);
            return $this->redirectToRoute('aabh_user_edit', array('id' => $user->getId()));
        }
        return $this->render('AABHUserBundle:User:edit.html.twig', array('user' => $user, 'form' => $form->createView()));
    }
    
    private function recoverPass($id){
        
        $ab = $this->getDoctrine()->getManager();
        $query = $ab->createQuery(
            'SELECT u.password FROM AABHUserBundle:User u WHERE u.id= :id'
        )->setParameter('id', $id);
            
        $passactual = $query->getResult();
        
        return $passactual;
    }
    
    public function viewAction($id){
        
        $repository = $this->getDoctrine()->getRepository('AABHUserBundle:User');
        
        $user = $repository->find($id);
        
        if(!$user){
            $mensaje = $this->get('translator')->trans('User not found.');
            throw $this->createNotFounException($mensaje);
        }
        
        $deleteform = $this->createCustomForm($user->getId(), 'DELETE', 'aabh_user_delete');
        

        return  $this->render('AABHUserBundle:User:view.html.twig', array('user'=>$user, 'delete_form' => $deleteform->createview()));
    }
    
    public function deleteAction(Request $request, $id){
        $ab = $this->getDoctrine()->getManager();
        
        $user = $ab->getRepository('AABHUserBundle:User')->find($id);
        
        if(!$user){
            $mensaje = $this->get('translator')->trans('User not found.');
            throw $this->createNotFounException($mensaje);
        }
        
        $allUser = $ab->getRepository('AABHUserBundle:User')->findAll();
        $countUser = count($allUser);
         
        //$form = $this->createDeleteForm($user);
        $form = $this-> createCustomForm($user -> getID(), 'DELETE', 'aabh_user_delete');
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid()){
            
            if($request -> isXMLHttpRequest()){
                $res = $this-> deleteUser($user->getRole(), $ab, $user);
                
                return new Response(
                    json_encode(array('removed'=>$res['removed'], 'message'=>$res['message'], 'countUser'=>$countUser)),
                    200,
                    array('Content-Type' => 'application/json')
                );
            }
            
            /*$ab->remove($user);
            $ab->flush();
            
            $mensaje = $this->get('translator')->trans('The user has been deleted.');*/
            
            $res = $this->deleteUser($user->getRole(), $ab, $user);
            $this->addFlash($res['alert'], $res['message']);
            return $this->redirectToRoute('aabh_user_index');
        }
    }
    
    private function deleteUser($role, $ab, $user){
        if($role == 'ROLE_USER'){
            $ab->remove($user);
            $ab->flush();
            
            $message = $this->get('translator')->trans('The user has been deleted.');
            $removed = 1;
            $alert = 'mensaje';
        } elseif($role == 'ROLE_ADMIN') {
            $message = $this->get('translator')->trans('The user could not be deleted.');
            $removed = 0;
            $alert = 'error';
        }
        
        return array('removed' => $removed, 'message' => $message, 'alert' => $alert);
    }
    
    private function createCustomForm($id, $method, $route){
        return $this->createFormBuilder()
            ->setAction($this->generateURL($route, array('id'=>$id)))
            ->setMethod($method)
            ->getForm();
    }

}

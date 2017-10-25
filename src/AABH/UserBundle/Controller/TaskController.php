<?php

namespace AABH\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AABH\UserBundle\Entity\Task;
use AABH\UserBundle\Form\TaskType;

class TaskController extends Controller
{
    public function indexAction(Request $request)
    {
        $ab = $this->getDoctrine()->getManager();
        $dql = "SELECT t FROM AABHUserBundle:Task t ORDER BY t.id DESC";
        $tasks = $ab->createQuery($dql);
        
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $tasks,
            $request->query->getInt('page', 1),
            3
        );
        
        return $this->render('AABHUserBundle:Task:index.html.twig', array('pagination' => $pagination));
    }
    
    public function customAction(Request $request)
    {
        $idUser = $this->get('security.token_storage')->getToken()->getUser()->getId();
        
        $ab = $this->getDoctrine()->getManager();
        $dql = "SELECT t FROM AABHUserBundle:Task t JOIN t.user u WHERE u.id = :idUser ORDER BY t.id DESC";
        
        $tasks = $ab->createQuery($dql)->setParameter('idUser' , $idUser);
        
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $tasks,
            $request->query->getInt('page', 1),
            3
        );
        
        $updateForm = $this->createCustomForm(':TASK_ID', 'PUT', 'aabh_task_process');
        
        return $this->render('AABHUserBundle:Task:custom.html.twig', array('pagination' => $pagination, 'update_form' => $updateForm->createView()));
    }
    
    public function processAction($id, Request $request)
    {
        $ab = $this->getDoctrine()->getManager();
        
        $task = $ab->getRepository('AABHUserBundle:Task')->find($id);
        
        if(!$task)
        {
            throw $this>createNotFoundException('Task not found');
        }
        
        $form = $this->createCustomForm($task->getId(), 'PUT', 'aabh_task_process');
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid())
        {
            
            $successMessage = $this->get('translator')->trans('The task has been processed.');
            $warningMessage = $this->get('translator')->trans('The task has already been processed.');
            
            if ($task->getStatus() == 0)
            {
                $task->setStatus(1);
                $ab->flush();
                
                if($request->isXMLHttpRequest())
                {
                    return new Response(
                        json_encode(array('processed' => 1, 'success' => $successMessage)),
                        200,
                        array('Content-Type' => 'application/json')
                    );
                }
            }
            else
            {
                if($request->isXMLHttpRequest())
                {
                    return new Response(
                        json_encode(array('processed' => 0, 'warning' => $warningMessage)),
                        200,
                        array('Content-Type' => 'application/json')
                    );
                }            
            }
        }
    }
    
    public function addAction()
    {
        $task = new Task();
        $form = $this->createCreateForm($task);
        
        return $this->render('AABHUserBundle:Task:add.html.twig', array('form' => $form->createView()));
    }
    
    private function createCreateForm(Task $entity)
    {
        $form = $this->createForm(new TaskType(), $entity, array(
            'action' => $this->generateUrl('aabh_task_create'),
            'method' => 'POST'
        ));
        
        return $form;
    }
    
    public function createAction(Request $request)
    {
        $task = new Task();
        $form = $this->createCreateForm($task);
        $form->handleRequest($request);
        
        if($form->isValid())
        {
            $task->setStatus(0);
            $ab = $this->getDoctrine()->getManager();
            $ab->persist($task);
            $ab->flush();
            
            $successMessage = $this->get('translator')->trans('The task has been created.');
            $this->addFlash('mensaje', $successMessage);            
            
            return $this->redirectToRoute('aabh_task_index');
        }
        
        return $this->render('AABHUserBundle:Task:add.html.twig', array('form' => $form->createView()));
    }
    
    public function viewAction($id)
    {
        $task = $this->getDoctrine()->getRepository('AABHUserBundle:Task')->find($id);
        
        if(!$task)
        {
            throw $this->createNotFoundException('The task does not exist.');
        }
        
        $deleteForm = $this->createCustomForm($task->getId(), 'DELETE', 'aabh_task_delete');
        
        $user = $task->getUser();
        
        return $this->render('AABHUserBundle:Task:view.html.twig', array('task' => $task, 'user' => $user, 'delete_form' => $deleteForm->createView()));
    }
    
    public function editAction($id)
    {
        $ab = $this->getDoctrine()->getManager();
        
        $task = $ab->getRepository('AABHUserBundle:Task')->find($id);
        
        if(!$task)
        {
            throw $this->createNotFoundException('task not found');
        }
        
        $form = $this->createEditForm($task);
        
        return $this->render('AABHUserBundle:Task:edit.html.twig', array('task' => $task, 'form' => $form->createView()));
    }
    
    private function createEditForm(Task $entity)
    {
        $form = $this->createForm(new TaskType(), $entity, array(
            'action' => $this->generateUrl('aabh_task_update', array('id' => $entity->getId())),
            'method' => 'PUT'
        ));
        
        return $form;
    }
    
    public function updateAction($id, Request $request)
    {
        $ab = $this->getDoctrine()->getManager();
        
        $task = $ab->getRepository('AABHUserBundle:Task')->find($id);
        
        if(!$task)
        {
            throw $this->createNotFoundException('task not found');
        }
        
        $form = $this->createEditForm($task);
        $form->handleRequest($request);
        
        if($form->isSubmitted() and $form->isValid())
        {
            $task->setStatus(0);
            $ab->flush();
            $successMessage = $this->get('translator')->trans('The task has been modified.');
            $this->addFlash('mensaje', $successMessage);            
            return $this->redirectToRoute('aabh_task_edit', array('id' => $task->getId()));
        }
        
        return $this->render('AABHUserBundle:Task:edit.html.twig', array('task' => $task, 'form' => $form->createView()));
    }
    
    public function deleteAction(Request $request, $id)
    {
        $ab = $this->getDoctrine()->getManager();
        
        $task = $ab->getRepository('AABHUserBundle:Task')->find($id);
        
        if(!$task)
        {
            throw $this->createNotFoundException('task not found');
        }
        
        $form = $this->createCustomForm($task->getId(), 'DELETE', 'aabh_task_delete');
        $form->handleRequest($request);
        
        if($form->isSubmitted() and $form->isValid())
        {
            $ab->remove($task);
            $ab->flush();
            
            $successMessage = $this->get('translator')->trans('The task has been deleted.');
            $this->addFlash('mensaje', $successMessage); 
            
            return $this->redirectToRoute('aabh_task_index');
        }
    }
    
    private function createCustomForm($id, $method, $route)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl($route, array('id' => $id)))
            ->setMethod($method)
            ->getForm();
    }
}

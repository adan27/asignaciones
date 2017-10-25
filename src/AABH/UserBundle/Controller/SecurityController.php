<?php

namespace AABH\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class SecurityController extends Controller
{
    public function loginAction(){
        $authenticationUtils = $this->get('security.authentication_utils');
        
        $error = $authenticationUtils->getLastAuthenticationError();
        
        $lastUserName = $authenticationUtils->getLastUsername();
        
        return $this->render('AABHUserBundle:Security:login.html.twig', array('last_username' => $lastUserName, 'error'=>$error));
    }
    
    public function loginCheckAction(){
        
    }
}

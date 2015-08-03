<?php

namespace WykopBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use WykopBundle\Entity\Training;
use WykopBundle\Entity\Distance;
use WykopBundle\Form\TrainingType;

/**
 * Training controller.
 *
 * @Route("/training")
 */
class TrainingController extends Controller
{

    /**
     * Lists all Training entities.
     *
     * @Route("/", name="training")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('WykopBundle:Training')->findAll();

        return array(
            'entities' => $entities,
        );
    }
    /**
     * Creates a new Training entity.
     *
     * @Route("/", name="training_create")
     * @Method("POST")
     * @Template("WykopBundle:Training:new.html.twig")
     */
    public function createAction(Request $request){
	$em = $this->getDoctrine()->getManager();
        $entity = new Training();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

	$distances = $entity->getDistance();

	if ($this->isFormTrainingValid($form, $distances)){
	    
	    $training = new Training();
	    $training->setTag($entity->getTag());
	    $training->setCity($entity->getCity());
	    $training->setNameUser($entity->getNameUser());
	    
	    $training_details_provider = $this->get('getTrainingDetails');

	    //Deal with multiple trainings in one form
	    foreach($distances as $index => $dist){
		$distance = new Distance();
		
		//Send distance to @getTrainingDetails - retrvie details
		$training_details = $training_details_provider->get($dist);

		//Set details about every distance
		if(count($training_details) > 1){
		    $distance->setLink($dist);
		}
		
		$value_distance = $training_details['distance'];
		
		
		$value_distance = preg_replace('/[^\.\,0-9]+/', '', $value_distance);
		$value_distance = preg_replace('/[\,]+/', '.', $value_distance);
		
		if($training->getTag()->getRound()){
		    $value_distance = round($value_distance);
		}else{
		    
		    $value_distance = number_format((float)$value_distance, 2, '.', '');
		}
			
		$distance->setDistance($value_distance);
		
		if(isset($training_details['start_time'])){
		    $distance->setStartDate($training_details['start_time']);
		}elseif(isset($entity->getDates()[$index])){
		    $distance->setStartDate($entity->getDates()[$index]);
		}else{
		    $distance->setStartDate(new \DateTime('now'));
		}
		
		if(isset($training_details['duration'])){
		    $distance->setDuration($training_details['duration']);
		}
		
		if(isset($training_details['speed_avg']))
		    $distance->setAvgSpeed($training_details['speed_avg']);
		
		if(isset($training_details['calories']))
		    $distance->setCalories($training_details['calories']);
		
		if(isset($training_details['training']))
		    $distance->setDetails($training_details['training']);
	
		$em->persist($distance);
		$training->setDistance($distance);
	
		
	    }

	    //Set username from session
	    $training->setNameUser('ANONIMOWO');
	    
	    //Get last distance
	    //$lastDistance = $this->get('LastDistance');
	    //$lastDistance->get($entity->getTag()->getName());
	    
	    //Subtract distances, build operation
	    //Compile new entry
	    
	    //Send new entry to Wykop
	    
	    $em->persist($training);
	    $em->flush();
	    dump($training);
	    
	    //If Success then redirect to Index(or training_show?)
	    //elseif forwardTo Index -> with all data from form
	    
            return $this->redirect($this->generateUrl('training_show', array('id' => $training->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a Training entity.
     *
     * @param Training $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Training $entity)
    {
        $form = $this->createForm(new TrainingType(), $entity, array(
            'action' => $this->generateUrl('training_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Dodaj', 'attr' => array('class' => 'btn')));

        return $form;
    }

    /**
     * Displays a form to create a new Training entity.
     *
     * @Route("/new", name="training_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Training();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Training entity.
     *
     * @Route("/{id}", name="training_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('WykopBundle:Training')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Training entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Training entity.
     *
     * @Route("/{id}/edit", name="training_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('WykopBundle:Training')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Training entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
    * Creates a form to edit a Training entity.
    *
    * @param Training $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Training $entity)
    {
        $form = $this->createForm(new TrainingType(), $entity, array(
            'action' => $this->generateUrl('training_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing Training entity.
     *
     * @Route("/{id}", name="training_update")
     * @Method("PUT")
     * @Template("WykopBundle:Training:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('WykopBundle:Training')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Training entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('training_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a Training entity.
     *
     * @Route("/{id}", name="training_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('WykopBundle:Training')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Training entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('training'));
    }

    /**
     * Creates a form to delete a Training entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('training_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }
    
    private function isFormTrainingValid($form, $distances){
	
	$valid = false;
	
	if(count($distances) <= 0){
	    $error = new FormError("Musisz podać co najmniej jeden wynik");
	    $form->get('distance')->addError($error);
	}else{
	    $valid = true;
	}
	
	foreach($distances as $distance)
	    if($distance == 0){
		    $valid = false;
		    
		    $error = new FormError("Musisz podać co najmniej jeden wynik, każdy musi być większy od 0");
		    $form->get('distance')->addError($error);
	    }
	
	return $form->isValid() && $valid;
    }
}

<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Form\UserEditForm;
use AppBundle\Form\UserForm;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class UserManagerController
 * @package AppBundle\Controller
 *
 * @Route("/users")
 */
class UserManagerController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/list", name="list_users")
     */
    public function listAction(Request $request)
    {
        return $this->render(
            '@App/list_users.html.twig',
            [
                'users' => $this->getDoctrine()->getRepository('AppBundle:User')->findAll(),
            ]
        );
    }

    /**
     * @param Request $request
     *
     * @Route("/add")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $user = new User();
        $form = $this->createForm(UserForm::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);

            $user->setRoles([$user->getStaffRole()]);

            $entityManager = $this->getDoctrine()->getManager();
            if ($entityManager->getRepository('AppBundle:User')->findOneBy(
                    ['username' => $user->getUsername()]
                ) !== null) {
                $form->get('username')->addError(new FormError('Already a user with this username'));
            } else {
                if ($entityManager->getRepository('AppBundle:User')->findOneBy(
                        ['email' => $user->getEmail()]
                    ) !== null) {
                    $form->get('email')->addError(new FormError('Already a user with this email'));
                } else {
                    $user->setEnabled(true);
                    $entityManager->persist($user);
                    $entityManager->flush();

                    return $this->redirectToRoute('list_users');
                }
            }

        }

        return $this->render('@App/create_user.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     *
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @Route("/edit/{id}", name="edit_user")
     */
    public function editAction(Request $request, UserPasswordEncoderInterface $passwordEncoder, $id)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $user = $entityManager->getRepository("AppBundle:User")->findOneBy(['id' => $id]);
        $form = $this->createForm(UserEditForm::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);
            $entityManager = $this->getDoctrine()->getManager();
            //TODO: Add User Level Update Support
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('list_users');

        }

        return $this->render('@App/edit_user.html.twig', ['form' => $form->createView(), 'user' => $user]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/delete/{id}", name="delete_user")
     */
    public function deleteAction(Request $request, $id)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $repository = $this->getDoctrine()->getRepository("AppBundle:Appointment");
        $user = $entityManager->getRepository("AppBundle:User")->findOneBy(['id' => $id]);
        $userAppointments = $repository->findBy(
            ['user' => $entityManager->getRepository('AppBundle:User')->findBy(['id' => $id])]
        );
        if ($user->getAppointments() !== null) {
            foreach ($userAppointments as $a) {
                $entityManager->remove($a);
            }
        }
        $entityManager->remove($user);
        $entityManager->flush();

        return $this->redirectToRoute('list_users');
    }
}

<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\User;
use App\Services\TranslatorService;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User as UserEntity;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends BaseController
{
    private $userModel;
    private $validator;
    private $translatorService;
    private $request;
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    { 
        $this->userModel = new User();
        $this->validator = Validation::createValidatorBuilder()->getValidator();
        $this->request = Request::createFromGlobals(); 
        $this->translatorService = new TranslatorService($this->request);
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function index()
    {
        $users = $this->userModel->getAll();
        $this->sendJsonResponse($users);
    }

    public function show($id)
    {
        $user = $this->userModel->find($id);
        $this->sendJsonResponse($user);
    }

    public function create()
    {
        $this->sendJsonResponse(['message' => 'Para crear un usuario, envía una petición POST a /users con los datos del usuario.']);
    }

    public function store()
    {
        $data = json_decode($this->request->getContent(), true);
        // Verificar si se recibieron datos
        if (empty($data)) {
            $this->sendJsonResponse(['status' => 'error', 'message' => $this->translatorService->trans('No data received', [], 'validators')], 400);
            return;
        }
        // Validar los datos recibidos
        $constraints = new Assert\Collection([
            'first_name' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Length(['min' => 1, 'max' => 255])],
            'last_name' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Length(['min' => 1, 'max' => 255])],
            'email' => [new Assert\NotBlank(), new Assert\Email()],
            'username' => [new Assert\NotBlank(), new Assert\Regex(['pattern' => '/^[a-zA-Z0-9]+$/']), new Assert\Length(['min' => 3, 'max' => 50])],
            'password' => [new Assert\NotBlank(), new Assert\Length(['min' => 6])],
        ]);

        $violations = $this->validator->validate($data, $constraints);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $params = $violation->getParameters();

                // Verifica si '{{ limit }}' está definido antes de agregar '%count%'
                if (isset($params['{{ limit }}'])) {
                    $params['%count%'] = $params['{{ limit }}'];
                }

                $errors[$violation->getPropertyPath()] = $this->translatorService->trans(
                    $violation->getMessageTemplate(),
                    $params,
                    'validators'
                );
            }
            $this->sendJsonResponse(['status' => 'errors', 'message' => $errors], 400);
            return;
        }
        
        $existing = $this->userModel->findByEmailOrUsername($data['email'], $data['username']);
        if ($existing === 'email') {
            $this->sendJsonResponse(['status' => 'error', 'message' => $this->translatorService->trans('The email already exists', [], 'validators')], 409);
            return;
        }
        if ($existing === 'username') {
            $this->sendJsonResponse(['status' => 'error', 'message' => $this->translatorService->trans('The username already exists', [], 'validators')], 409);
            return;
        }
        if ($existing === false) {
            $this->sendJsonResponse(['status' => 'error', 'message' => $this->translatorService->trans('Error creating user in the database', [], 'validators')], 500);
            return;
        }

        try {
            $userEntity = new UserEntity();
            $plainPassword = $data['password'];

            // Hashear la contraseña utilizando el método correcto
            $hashedPassword = $this->userPasswordHasher->hashPassword($userEntity, $plainPassword);

            // Establecer la contraseña hasheada en la entidad
            $userEntity->setPassword($hashedPassword);

            // Reemplazar la contraseña plana en el array de datos con el hash
            $data['password'] = $hashedPassword;

            $newUserId = $this->userModel->create($data);

            header("Location: /users/{$newUserId}", true, 201);
            $this->sendJsonResponse([
                'status' => 'success',
                'message' => $this->translatorService->trans('User created successfully', [], 'validators'),
                'data' => [
                    'id' => $newUserId,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'username' => $data['username'],
                ],
                'location' => "/users/{$newUserId}"
            ], 201);

        } catch (\PDOException $e) {
            $this->sendJsonResponse(['status' => 'error', 'message' => $this->translatorService->trans('Error creating user in the database', [], 'validators')], 500);
            return;
        }
    }

    public function edit($id)
    {
        $user = $this->userModel->find($id);
        $this->sendJsonResponse(['message' => 'Para editar el usuario con ID ' . $id . ', envía una petición PUT a /users/' . $id . ' con los datos actualizados.', 'user' => $user]); // Usamos el método heredado
    }

    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $updatedRows = $this->userModel->update($id, $data);
        $this->sendJsonResponse(['rows_affected' => $updatedRows]); // Usamos el método heredado
    }

    public function destroy($id)
    {
        $deletedRows = $this->userModel->delete($id);
        if ($deletedRows > 0) {
            $this->sendJsonResponse(['message' => 'Usuario eliminado'], 204);
        } else {
            $this->sendJsonResponse(['message' => 'Usuario no encontrado'], 404);
        }
    }
}

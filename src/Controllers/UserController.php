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
        $allowed_params = ['page', 'limit', 'id_status', 'first_name', 'last_name', 'email', 'username', 'created_at'];
        
        // Recoger todos los parámetros de la query
        $params = $this->request->query->all();

        foreach ($params as $key => $value) {
            if (!in_array($key, $allowed_params)) {
                return $this->sendJsonResponse(['status' => 'error', 'message' => 'Invalid query parameter: '.$key , 'allowed parameters' => implode(', ',$allowed_params)], 400);
            }
        }

        // Definir las restricciones para cada parámetro
        $constraints = new Assert\Collection([
            // Para page y limit, se valida que sean dígitos (enteros en formato string)
            'page' => new Assert\Optional([
                new Assert\Regex([
                    'pattern' => '/^\d+$/',
                    'message' => "El parámetro 'page' debe ser un entero."
                ])
            ]),
            'limit' => new Assert\Optional([
                new Assert\Regex([
                    'pattern' => '/^\d+$/',
                    'message' => "El parámetro 'limit' debe ser un entero."
                ])
            ]),
            'id_status' => new Assert\Optional([
                new Assert\Regex([
                    'pattern' => '/^\d+$/',
                    'message' => "El parámetro 'id_status' debe ser un entero. Consulte los códigos de estado disponibles.",
                    'default states' => [
                        'active' => 1,
                        'inactive' => 2,
                        'deleted' => 3, 
                    ]
                ])
            ]),
            // Para los demás parámetros, se valida su tipo o formato
            'first_name' => new Assert\Optional([
                new Assert\Type([
                    'type' => 'string',
                    'message' => "El parámetro 'first_name' debe ser una cadena."
                ])
            ]),
            'last_name' => new Assert\Optional([
                new Assert\Type([
                    'type' => 'string',
                    'message' => "El parámetro 'last_name' debe ser una cadena."
                ])
            ]),
            'email' => new Assert\Optional([
                new Assert\Type([
                    'type' => 'string',
                    'message' => "El parámetro 'email' debe ser una cadena."
                ])
            ]),
            'username' => new Assert\Optional([
                new Assert\Type([
                    'type' => 'string',
                    'message' => "El parámetro 'username' debe ser una cadena."
                ])
            ]),
            'created_at' => new Assert\Optional([
                new Assert\Date([
                    'message' => "El parámetro 'created_at' debe tener un formato de fecha válido (YYYY-MM-DD)."
                ])
            ]),
        ]);

        // Validar los parámetros con las restricciones definidas
        $violations = $this->validator->validate($params, $constraints);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            return $this->sendJsonResponse(['status' => 'error', 'message' => $errors], 400);
        }

        // Filtrar parámetros válidos
        $filters = [];
        foreach ($allowed_params as $param) {
            if ($param === 'page' || $param === 'limit') {
                continue;
            }
            $value = $this->request->query->get($param, null);
            if ($value !== null && $value !== '') {
                $filters[$param] = trim($value);
            }
        }

        // Parámetros de paginación
        $page = (int) $this->request->query->get('page', 1);
        $limit = (int) $this->request->query->get('limit', 3);
        $offset = ($page - 1) * $limit;

        // Obtener usuarios con filtros
        $users = $this->userModel->getFilteredPaginated($filters, $limit, $offset);

        if ($users === false) {
            return $this->sendJsonResponse(['status' => 'error', 'message' => 'Database error occurred'], 500);
        }

        if (empty($users)) {
            return $this->sendJsonResponse(['status' => 'error', 'message' => 'No users found'], 404);
        }

        return $this->sendJsonResponse([
            'status' => 'success',
            'message' => 'User(s) found',
            'data' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit
            ]
        ], 200);
    }


    public function show($id)
    {
        $user = $this->userModel->find($id);

        if ($user === false) {
            return $this->sendJsonResponse(['status' => 'error', 'message' => 'Database error occurred'], 500);
        }

        if(empty($user)) {
            $this->sendJsonResponse(['status' => 'error', 'message' => 'User not found'], 404);
        }

        $this->sendJsonResponse(['status' => 'succes', 'message' => 'User found', 'data' => $user], 200);
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

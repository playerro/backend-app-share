<?php


namespace App\Services;


use App\Entity\User;
use App\Entity\UserAvatar;
use App\Repository\UserAvatarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserService
{
    protected $encoder;
    protected $em;

    public function __construct(UserPasswordEncoderInterface $encoder, EntityManagerInterface $em) {
         $this->encoder = $encoder;
         $this->em = $em;
    }

    public function createUser($password, $username, $firstName, $lastName, $email) {
     $user = new User();
     $user->setPassword($this->encoder->encodePassword($user, $password))
         ->setUsername($username)
         ->setFirstName($firstName)
         ->setLastName($lastName)
         ->setEmail($email)
         ->setRegdate(time())
         ->setGender(1)
         ->setLastActivity(time());
     //TODO: get correct ip and b-date
     $user->setIp('1234')
         ->setBirthday(time());
    return $user;
 }

 public function getDetails(User $user) {
    $data = [
        'id'=>$user->getId(),
        'username'=>$user->getUsername(),
        'firstName'=>$user->getFirstName(),
        'lastName'=>$user->getLastName(),
        'isAdmin'=>$user->getIsAdmin(),
        'lastActivity'=>$user->getLastActivity(),
        'regDate'=>$user->getRegdate(),
        'gender'=>$user->getGender(),
        'status'=>$user->getStatus(),
        'birthday'=>$user->getBirthday(),
    ];
     /* @var $avatarRepository UserAvatarRepository */
     /* @var $avatar UserAvatar */
     $avatarRepository = $this->em->getRepository(UserAvatar::class);
     $avatar = $avatarRepository->findOneLatest($user->getId());
     if ($avatar) {
         $data['avatar'] = $avatar->getFilename();
     }

    return $data;
 }

 public function updateUser(Request $request, User $user) {
     $firstName = $request->get('firstName');
     $lastName = $request->get('lastName');
     $status = $request->get('status');
     $birthday = strtotime($request->get('birthday'));
     $gender = $request->get('gender');
     if (empty($firstName) || empty($lastName)) return false;
     $newUser = $user->setFirstName($firstName)->setLastName($lastName)->setStatus($status)->setBirthday($birthday)->setGender($gender);
     $this->em->persist($newUser);
     $this->em->flush();
     return $newUser;
 }
 public function checkAttributeExists(string $type, string $attr) {
     $userRepository = $this->em->getRepository(User::class);
     return ($userRepository->findBy([$type=>$attr]) != null);
 }
}

<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use openvk\Web\Models\Entities\User;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;
use Chandler\Security\User as ChandlerUser;

class Users
{
    private $context;
    private $users;
    
    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->users   = $this->context->table("profiles");
    }
    
    private function toUser(?ActiveRow $ar): ?User
    {
        return is_null($ar) ? NULL : new User($ar);
    }
    
    function get(int $id): ?User
    {
        return $this->toUser($this->users->get($id));
    }
    
    function getByShortURL(string $url): ?User
    {
        return $this->toUser($this->users->where("shortcode", $url)->fetch());
    }
    
    function getByChandlerUser(ChandlerUser $user): ?User
    {
        return $this->toUser($this->users->where("user", $user->getId())->fetch());
    }
    
    function find(string $query): Util\EntityStream
    {
        $query  = "%$query%";
        $result = $this->users->where("CONCAT_WS(' ', first_name, last_name) LIKE ?", $query);
        
        return new Util\EntityStream("User", $result);
    }
    
    function getStatistics(): object
    {
        return (object) [
            "all"    => sizeof(clone $this->users),
            "active" => sizeof((clone $this->users)->where("online > 0")),
            "online" => sizeof((clone $this->users)->where("online >= ?", time() - 900)),
        ];
    }

    function getByAddress(string $address): ?User
    {
        if(substr_compare($address, "/", -1) === 0)
            $address = substr($address, 0, iconv_strlen($address) - 1);

        $serverUrl = ovk_scheme(true) . $_SERVER["SERVER_NAME"];
        if(strpos($address, $serverUrl . "/") === 0)
            $address = substr($address, iconv_strlen($serverUrl) + 1);

        if(strpos($address, "id") === 0) {
            $user = $this->get((int) substr($address, 2));
            if($user) return $user;
        }

        return $this->getByShortUrl($address);
    }
    
    use \Nette\SmartObject;
}

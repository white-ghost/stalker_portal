<?php

namespace Stalker\Lib\RESTAPI\v2;

class RESTApiResourceVideoFavorites extends RESTApiCollection
{
    protected $params_map = array("users" => "users.login");
    private   $user_id;
    private   $manager;

    public function __construct(array $nested_params, array $external_params){

        parent::__construct($nested_params, $external_params);

        if (empty($this->nested_params['users.login'])){
            throw new RESTBadRequest("User must be specified");
        }

        $user_login = $this->nested_params['users.login'];

        $stb = \Stb::getInstance();
        $user = $stb->getByLogin($user_login);

        if (empty($user)){
            throw new RESTNotFound("User not found");
        }

        $this->user_id = $user['id'];
        $this->manager = \Vod::getInstance();
    }

    public function getCount(RESTApiRequest $request){
        throw new RESTNotFound("Resource not support count");
    }

    public function get(RESTApiRequest $request){

        return $this->filter($this->manager->getFavByUid($this->user_id));
    }

    public function update(RESTApiRequest $request){

        $data = $request->getData();

        if (!isset($data['video_id'])){
            throw new RESTBadRequest("Favorite videos required");
        }

        if (empty($data['video_id'])){
            $new_favorites = array();
        }else{
            $new_favorites = explode(",", $data['video_id']);
        }

        $result = $this->manager->saveFav($new_favorites, $this->user_id);

        if (!$result){
            throw new RESTServerError("Error while saving favorites");
        }

        return (bool) $result;
    }

    public function create(RESTApiRequest $request){

        $new_favorite = (int) $request->getData('video_id');

        if (empty($new_favorite) || $new_favorite <= 0){
            throw new RESTBadRequest("Favorite video required");
        }

        $favorites = $this->manager->getFavByUid($this->user_id);

        $idx = array_search($new_favorite, $favorites);

        if ($idx !== false){
            array_splice($favorites, $idx, 1);
        }

        $favorites[] = (string) $new_favorite;

        $result = $this->manager->saveFav($favorites, $this->user_id);

        if (!$result){
            throw new RESTServerError("Error while saving favorites");
        }

        return (bool) $result;
    }

    private function filter($list){

        $list = array_map(function($item){
            return (int) $item;
        }, $list);

        return $list;
    }
}
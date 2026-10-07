<?php
namespace App\Services;

use App\Models\User;

class UserService{

    public function store($data)
    {
        $user = User::create($data);
        $user->assignRole($data['rol']);
        if ($data['rol']=='practicing') {
            $user->practicing()->create([
                'discord_id' => $data['discord_id'],
                'area_id' => $data['area']
            ]);
        }

    }

}

?>

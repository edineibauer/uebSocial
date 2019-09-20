<?php
set_time_limit(0);
date_default_timezone_set('UTC');

use InstagramAPI\Instagram;
use InstagramAPI\Signatures;

/**
 * Este script irá deixar de seguir de 1 a 2 pessoas não favoritas de forma aleatória com preferência nas mais antigas
 * Só irá executar caso tenha mais que 6.500 seguidores, próximo de atingir o máximo de 7.500
 * Deixa de seguir na mesma proporção que segue novos perfis
 */

if (defined('INSTAGRAM_USER') && defined('INSTAGRAM_PASS') && !empty(INSTAGRAM_USER) && !empty(INSTAGRAM_PASS)) {

    //considerando 10 horas por dia de execução do script
    //horário comercial, intervalor das 8 - 12 / 14 - 19
    $hora = (int)date("H");

    if ($hora > 7 && $hora < 20 && $hora !== 12 && $hora !== 13) {

        //Divide o número de follows dia por 2 visto que o script tem probabilidades de seguir o dobro (mantendo uma média aproximada e não exata (simulação humana))
        //Divide o número de follow dia por 10 para saber quantos seguir por hora
        $followHora = ((defined('INSTAGRAM_FOLLOW_DAY') ? (INSTAGRAM_FOLLOW_DAY < 500 && INSTAGRAM_FOLLOW_DAY > 0 ? INSTAGRAM_FOLLOW_DAY : 500) : 100) / 2) / 10;
        $minuto = (int)date("i");

        //Divide o número de follows por hora para saber em qual minuto ele deve seguir alguém
        if ($minuto % $followHora === 0) {

            Instagram::$allowDangerousWebUsageAtMyOwnRisk = true;
            $ig = new Instagram(!1, !1);

            try {
                $ig->login(INSTAGRAM_USER, INSTAGRAM_PASS);

                $rankToken = Signatures::generateUUID();
                $usersFollowing = $ig->people->getFollowing($userId, $rankToken)->getUsers();

                //se já estiver seguindo mais de 6500, começa a deixar de seguir
                if(count($usersFollowing) > 6500) {

                    $countUnfollow = 1;
                    $unfollowCount = rand(1, 2);
                    //para cada um que sigo
                    foreach ($usersFollowing as $i => $following) {

                        //com excessão dos favoritos
                        if (!$following->getIsFavorite()) {

                            //de forma aleatória, mas com preferência nos mais antigos
                            if(rand(0, 100) === 1) {
                                $ig->people->unfollow($following->getPk());
                                $countUnfollow ++;

                                if($countUnfollow > $unfollowCount)
                                    break;
                                else
                                    sleep(rand(4,12));
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                exit(0);
            }
        }
    }
}
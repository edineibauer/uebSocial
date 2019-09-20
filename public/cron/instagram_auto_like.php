<?php
set_time_limit(0);
date_default_timezone_set('UTC');

use InstagramAPI\Instagram;

/**
 * Este script irá dar de 1 a 2 likes nas sugestões do instagram em intervalos de tempo aleatório
 * de acordo com o número de likes dia desejado
 */

if (defined('INSTAGRAM_USER') && defined('INSTAGRAM_PASS') && !empty(INSTAGRAM_USER) && !empty(INSTAGRAM_PASS)) {

    //considerando 10 horas por dia de execução do script
    //horário comercial, intervalor das 8 - 12 / 14 - 19
    $hora = (int)date("H");

    if ($hora > 7 && $hora < 20 && $hora !== 12 && $hora !== 13) {

        //Divide o número de likes dia por 2 visto que o script tem probabilidades de dar like em dobro (mantendo uma média aproximada e não exata (simulação humana))
        //Divide o número de likes dia por 10 para saber quantos seguir por hora
        $followHora = ((defined('INSTAGRAM_LIKE_DAY') ? (INSTAGRAM_LIKE_DAY < 500 && INSTAGRAM_LIKE_DAY > 0 ? INSTAGRAM_LIKE_DAY : 500) : 100) / 2) / 10;
        $minuto = (int)date("i");

        //Divide o número de likes por hora para saber em qual minuto ele deve dar like
        if ($minuto % $followHora === 0) {

            Instagram::$allowDangerousWebUsageAtMyOwnRisk = true;
            $ig = new Instagram(!1, !1);

            try {
                $ig->login(INSTAGRAM_USER, INSTAGRAM_PASS);

                //50% de chance de dar 2 likes nessa execução
                $maxLike = rand(1, 2);

                $countLike = 1;
                foreach ($ig->people->getSuggestedUsers(INSTAGRAM_ID)->getUsers() as $user) {

                    //5% de probabilidade de escolher essa pessoa para dar like no seu conteúdo
                    if (rand(0, 20) === 1) {
                        $response = $ig->timeline->getUserFeed($user->getPk(), null);
                        $ig->media->like($response->getItems()[0]->getId(), 0);
                        
                        $countLike ++;
                        if($countLike > $maxLike) {
                            break;
                        } else {
                            //aguarda alguns segundos para simular um novo like
                            sleep(rand(15, 30));
                        }
                    }
                }
            } catch (\Exception $e) {
                exit(0);
            }
        }
    }
}
<?php
set_time_limit(0);
date_default_timezone_set('UTC');

use InstagramAPI\Instagram;

/**
 * Este script irá seguir de 1 a 3 sugestões do instagram em intervalos de tempo aleatório
 * de acordo com o número de follow dia desejado
 *
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

                $peoples = $ig->people->getSuggestedUsers(INSTAGRAM_ID)->getUsers();

                //segue a primeira sugestão do instagram
                if(!empty($peoples[0]))
                    $ig->people->follow($peoples[0]->getPk());

                //50% de probabilidade de seguir uma segunda pessoa
                if (rand(0, 1) === 1) {

                    //aguarda alguns segundos para simular uma ação humana
                    sleep(rand(2, 5));

                    //segue a segunda sugestão do instagram
                    if(!empty($peoples[1]))
                        $ig->people->follow($peoples[1]->getPk());

                    //25% de probabilidade sobre a 50% anterior de seguir uma terceira pessoa
                    if (rand(0, 3) === 1) {

                        //aguarda alguns segundos para simular uma ação humana
                        sleep(rand(2, 10));

                        //segue a terceira sugestão do instagram
                        if(!empty($peoples[2]))
                            $ig->people->follow($peoples[2]->getPk());
                    }
                }
            } catch (\Exception $e) {
                exit(0);
            }
        }
    }
}
<?php

/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2022/2/15 11:57 AM
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Model;

class Coroutine
{

    private static array $connects=[];

    /**
     * @param int $coId
     * @return Medoo|null
     */
    public static function getCon(int $coId): ?Medoo {
        return  self::$connects[$coId]??null;
    }

    /**
     * @param int $coId
     * @param Medoo $medoo
     * @return void
     */
    public static function setCon(int $coId,Medoo $medoo):void {
        self::$connects[$coId] = $medoo;
    }

    public static function delCon(int $coId):void{
        unset(self::$connects[$coId]);
    }
}
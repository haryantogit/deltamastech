<?php

namespace App\Helpers;

class NumberHelper
{
    public static function terbilang($number)
    {
        $number = abs(intval($number));

        if ($number == 0)
            return 'Nol';

        $words = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
        $result = "";

        if ($number < 12) {
            $result = $words[$number];
        } elseif ($number < 20) {
            $result = self::terbilang($number - 10) . " Belas";
        } elseif ($number < 100) {
            $sisa = $number % 10;
            $result = self::terbilang(intval($number / 10)) . " Puluh" . ($sisa > 0 ? " " . self::terbilang($sisa) : "");
        } elseif ($number < 200) {
            $sisa = $number - 100;
            $result = "Seratus" . ($sisa > 0 ? " " . self::terbilang($sisa) : "");
        } elseif ($number < 1000) {
            $sisa = $number % 100;
            $result = self::terbilang(intval($number / 100)) . " Ratus" . ($sisa > 0 ? " " . self::terbilang($sisa) : "");
        } elseif ($number < 2000) {
            $sisa = $number - 1000;
            $result = "Seribu" . ($sisa > 0 ? " " . self::terbilang($sisa) : "");
        } elseif ($number < 1000000) {
            $sisa = $number % 1000;
            $result = self::terbilang(intval($number / 1000)) . " Ribu" . ($sisa > 0 ? " " . self::terbilang($sisa) : "");
        } elseif ($number < 1000000000) {
            $sisa = $number % 1000000;
            $result = self::terbilang(intval($number / 1000000)) . " Juta" . ($sisa > 0 ? " " . self::terbilang($sisa) : "");
        } elseif ($number < 1000000000000) {
            $sisa = $number % 1000000000;
            $result = self::terbilang(intval($number / 1000000000)) . " Miliar" . ($sisa > 0 ? " " . self::terbilang($sisa) : "");
        } elseif ($number < 1000000000000000) {
            $sisa = $number % 1000000000000;
            $result = self::terbilang(intval($number / 1000000000000)) . " Triliun" . ($sisa > 0 ? " " . self::terbilang($sisa) : "");
        }

        return trim($result);
    }

    public static function formatRupiah($number)
    {
        return "Rp " . number_format($number, 0, ',', '.');
    }
}

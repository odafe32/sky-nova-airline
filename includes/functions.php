<?php 

function redirectToLogin(){
    header("location:../login.php");
    die();
}

// Format amounts consistently in Nigerian Naira (₦)
if (!function_exists('formatCurrency')) {
    function formatCurrency($amount): string {
        $num = is_numeric($amount) ? (float)$amount : 0.0;
        return '₦' . number_format($num, 2);
    }
}
?>
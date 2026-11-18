<?php
session_start();
include "koneksi.php";
if(isset($_POST['submit'])){
    $username = $_POST['username'];
    $password = $_POST['password'];
    $result = mysqli_query($koneksi, "SELECT * FROM pengguna WHERE namapengguna = '$username' AND password = '$password'");
    if(mysqli_num_rows($result)>0){//sedikit belajar dari ai yang num rows dan fetch assoc
        $row=mysqli_fetch_assoc($result);
        $_SESSION['username'] =$_POST['username'];;
        $_SESSION['password'] =$_POST['password'];;
        $_SESSION['status'] = true;
        header("location: index.php");
        
        exit();
    }else{
        echo "<p>username atau password salah</p>";
    }
}
?>
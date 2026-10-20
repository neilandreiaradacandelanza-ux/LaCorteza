<?php

function getProfile($connect2db, $userId)
{
    $stmt = mysqli_prepare($connect2db, "SELECT firstname, lastname, email FROM accounts WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

function updateProfile($data, $connect2db, $userId, &$resultClass, &$result)
{
    $firstname = $data['firstname'];
    $lastname  = $data['lastname'];
    $email     = $data['email'];

    $stmt = mysqli_prepare($connect2db, "UPDATE accounts SET firstname = ?, lastname = ?, email = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "sssi", $firstname, $lastname, $email, $userId);

    if (!mysqli_stmt_execute($stmt)) {
        $resultClass = "error";
        $result = mysqli_error($connect2db);
        return;
    }

    $resultClass = "success";
    $result = "Updated Successfully";

    $_SESSION['user']['firstname'] = $firstname;
    $_SESSION['user']['lastname'] = $lastname;
    $_SESSION['user']['email'] = $email;
}

function createPost($connect2db, $userId, $data, &$resultClass, &$result)
{
    $postContent = $data['postContent'];

    $stmt = mysqli_prepare($connect2db, "INSERT INTO posts (postContent, userID) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, "si", $postContent, $userId);

    if (!mysqli_stmt_execute($stmt)) {
        $resultClass = "error";
        $result = mysqli_error($connect2db);
        return;
    }

    $resultClass = "success";
    $result = "Post Created Successfully";
}

function getPosts($connect2db, $userId) 
{
    $sql = "SELECT posts.*, accounts.firstname, accounts.lastname, accounts.email 
            FROM posts 
            LEFT JOIN accounts ON accounts.id = posts.userID 
            ORDER BY posts.id DESC";
            
    $query = mysqli_query($connect2db, $sql);

    $posts = [];
    if ($query) {
        while ($row = mysqli_fetch_assoc($query)) {
            $posts[] = $row;
        }
    }
    return $posts;
}
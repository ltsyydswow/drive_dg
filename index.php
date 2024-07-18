<?php
session_start();

function load_json($filename) {
    if (!file_exists($filename)) {
        file_put_contents($filename, json_encode([]));
    }
    $data = file_get_contents($filename);
    return json_decode($data, true);
}

function save_json($filename, $data) {
    file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
}

$users_file = 'users.json';
$files_file = 'files.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register'])) {
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $users = load_json($users_file);
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                echo "用户名已存在";
                exit;
            }
        }

        $users[] = ['username' => $username, 'password' => $password];
        save_json($users_file, $users);
        echo "注册成功";
    } elseif (isset($_POST['login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $users = load_json($users_file);
        foreach ($users as $user) {
            if ($user['username'] === $username && password_verify($password, $user['password'])) {
                $_SESSION['username'] = $username;
                header("Location: index.php");
                exit;
            }
        }
        echo "用户名或密码错误";
    } elseif (isset($_POST['upload']) && isset($_FILES['fileToUpload'])) {
        $username = $_SESSION['username'];
        $target_dir = "upload/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
        $uploadOk = 1;

        if (file_exists($target_file)) {
            echo "文件已存在。";
            $uploadOk = 0;
        }

        if ($_FILES["fileToUpload"]["size"] > 50000000) {
            echo "文件太大。";
            $uploadOk = 0;
        }

        if ($uploadOk == 0) {
            echo "抱歉，您的文件无法上传。";
        } else {
            if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
                echo "文件 ". htmlspecialchars(basename($_FILES["fileToUpload"]["name"])). " 已成功上传。";

                $files = load_json($files_file);
                $files[] = [
                    'username' => $username,
                    'filename' => basename($_FILES["fileToUpload"]["name"]),
                    'filesize' => $_FILES["fileToUpload"]["size"],
                    'upload_time' => date("Y-m-d H:i:s")
                ];
                save_json($files_file, $files);
            } else {
                echo "抱歉，上传文件时发生错误。";
            }
        }
    } elseif (isset($_POST['logout'])) {
        session_destroy();
        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- 视口设置 -->
    <title>网盘</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%; /* 宽度全屏 */
            padding: 20px; /* 增加填充 */
        }
        h1, h2 {
            text-align: center;
            color: #333;
        }
        .form-container {
            text-align: center;
            margin-top: 20px;
        }
        .form-container input[type="text"],
        .form-container input[type="password"],
        .form-container input[type="file"] {
            width: calc(100% - 22px); /* 调整输入框宽度适应较小屏幕 */
            padding: 10px;
            margin: 5px auto;
            display: block;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .form-container input[type="submit"] {
            width: 100%; /* 全宽度提交按钮 */
            margin-top: 10px;
            background-color: #f0ad4e;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .form-container input[type="submit"]:hover {
            background-color: #ec971f;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .pagination {
            text-align: center;
            margin-top: 20px;
        }
        .pagination a {
            margin: 0 5px;
            text-decoration: none;
            color: #4CAF50;
        }
        .pagination a:hover {
            text-decoration: underline;
        }
        .notice {
            margin-top: 20px;
            background-color: #ffdddd;
            padding: 10px;
            border-left: 6px solid #f44336;
        }
        @media (max-width: 600px) {
            .container {
                padding: 10px; 
            }
            .form-container input[type="text"],
            .form-container input[type="password"],
            .form-container input[type="file"] {
                width: calc(100% - 12px); 
                padding: 8px;
                margin: 3px auto;
            }
            .form-container input[type="submit"] {
                width: 100%; 
                margin-top: 8px;
            }
            table {
                overflow-x: auto; 
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>网盘</h1>

        <?php if (!isset($_SESSION['username'])): ?>
            <div class="form-container">
                <form action="index.php" method="post">
                    <input type="text" name="username" placeholder="用户名" required>
                    <input type="password" name="password" placeholder="密码" required>
                    <input type="submit" name="login" value="登录">
                    <input type="submit" name="register" value="注册">
                </form>
            </div>
        <?php else: ?>
            <h2>欢迎, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
            <div class="form-container">
                <form action="index.php" method="post" enctype="multipart/form-data">
                    <label for="fileToUpload">选择文件</label>
                    <input type="file" name="fileToUpload" id="fileToUpload">
                    <input type="submit" value="上传文件" name="upload">
                </form>
            </div>
            <table>
                <tr>
                    <th>#</th>
                    <th>用户名</th>
                    <th>文件名</th>
                    <th>文件大小</th>
                    <th>上传时间</th>
                    <th>操作</th>
                </tr>
                <?php
                $files = load_json($files_file);
                $counter = 1;
                foreach($files as $file) {
                    echo "<tr>";
                    echo "<td>" . $counter . "</td>";
                    echo "<td>" . htmlspecialchars($file['username']) . "</td>";
                    echo "<td>" . htmlspecialchars($file['filename']) . "</td>";
                    echo "<td>" . $file['filesize'] . " bytes</td>";
                    echo "<td>" . $file['upload_time'] . "</td>";
                    echo "<td><a href='upload/" . htmlspecialchars($file['filename']) . "' download>下载</a></td>";
                    echo "</tr>";
                    $counter++;
                }
                ?>
            </table>
            <div class="pagination">
                <a href="#">&laquo;</a>
                <a href="#">1</a>
                <a href="#">2</a>
                <a href="#">3</a>
                <a href="#">&raquo;</a>
            </div>
            <form action="index.php" method="post">
                <input type="submit" name="logout" value="退出登录">
            </form>
        <?php endif; ?>

        <div class="notice">
            <strong>公告通知:</strong> 禁止上传黄、赌、毒违规文件，后果自负！ 
        </div>
    </div>
</body>
</html>

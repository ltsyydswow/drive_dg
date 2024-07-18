<?php
session_start();

// json加载data
function load_json($filename) {
    if (!file_exists($filename)) {
        file_put_contents($filename, json_encode([]));
    }
    $data = file_get_contents($filename);
    return json_decode($data, true);
}

// 保存data
function save_json($filename, $data) {
    file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
}

$users_file = 'users.json';
$files_file = 'files.json';

// admin acc
$admin_username = 'admin';
$admin_password = password_hash('admin', PASSWORD_DEFAULT);

// 如果没有admin账号，创建admin
$users = load_json($users_file);
$admin_exists = false;
foreach ($users as $user) {
    if ($user['username'] === $admin_username) {
        $admin_exists = true;
        break;
    }
}
if (!$admin_exists) {
    $users[] = ['username' => $admin_username, 'password' => $admin_password];
    save_json($users_file, $users);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        foreach ($users as $user) {
            if ($user['username'] === $username && password_verify($password, $user['password'])) {
                $_SESSION['username'] = $username;
                header("Location: admin.php");
                exit;
            }
        }
        echo "用户名或密码错误";
    }
    // 删除user
    elseif (isset($_POST['delete_user'])) {
        $username_to_delete = $_POST['username_to_delete'];

        $filtered_users = array_filter($users, function($user) use ($username_to_delete) {
            return $user['username'] !== $username_to_delete;
        });

        save_json($users_file, array_values($filtered_users));
        echo "用户删除成功";
    }
    // 删除file
    elseif (isset($_POST['delete_file'])) {
        $filename_to_delete = $_POST['filename_to_delete'];

        $files = load_json($files_file);
        $filtered_files = array_filter($files, function($file) use ($filename_to_delete) {
            return $file['filename'] !== $filename_to_delete;
        });

        save_json($files_file, array_values($filtered_files));
        unlink('upload/' . $filename_to_delete); // 删除上传的文件
        echo "文件删除成功";
    }
    // 退出登录
    elseif (isset($_POST['logout'])) {
        session_destroy();
        header("Location: admin.php");
        exit;
    }
}

// 加载用户列表和文件列表
$users = load_json($users_file);
$files = load_json($files_file);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台管理</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            text-align: center;
            color: #333;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        ul li {
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        table th {
            background-color: #f2f2f2;
        }
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        form {
            display: inline-block;
        }
        form input[type="submit"] {
            background-color: #f0ad4e;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
        }
        form input[type="submit"]:hover {
            background-color: #ec971f;
        }
        .notice {
            margin-top: 20px;
            background-color: #ffdddd;
            padding: 10px;
            border-left: 6px solid #f44336;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!isset($_SESSION['username'])): ?>
            <h1>管理员登录</h1>
            <div class="form-container">
                <form action="admin.php" method="post">
                
                    <input type="text" name="username" placeholder="用户名" required>
                    <input type="password" name="password" placeholder="密码" required>

                    <input type="submit" name="login" value="登录">
                </form>
            </div>
        <?php else: ?>
            <h1>用户管理</h1>
            <h2>用户列表</h2>
            <ul>
                <?php foreach ($users as $user): ?>
                    <li>
                        <?php echo htmlspecialchars($user['username']); ?>
                        <?php if ($user['username'] !== 'admin'): ?>
                            <form action="admin.php" method="post">
                                <input type="hidden" name="username_to_delete" value="<?php echo htmlspecialchars($user['username']); ?>">
                                <input type="submit" name="delete_user" value="删除">
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <h1>文件管理</h1>
            <h2>文件列表</h2>
            <table>
                <tr>
                    <th>#</th>
                    <th>用户名</th>
                    <th>文件名</th>
                    <th>文件大小</th>
                    <th>上传时间</th>
                    <th>操作</th>
                </tr>
                <?php foreach ($files as $index => $file): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($file['username']); ?></td>
                        <td><?php echo htmlspecialchars($file['filename']); ?></td>
                        <td><?php echo $file['filesize']; ?> bytes</td>
                        <td><?php echo $file['upload_time']; ?></td>
                        <td>
                            <a href="upload/<?php echo htmlspecialchars($file['filename']); ?>" download>下载</a>
                            <form action="admin.php" method="post">
                                <input type="hidden" name="filename_to_delete" value="<?php echo htmlspecialchars($file['filename']); ?>">
                                <input type="submit" name="delete_file" value="删除">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <form action="admin.php" method="post">
                <input type="submit" name="logout" value="退出登录">
            </form>
        <?php endif; ?>

        <div class="notice">
            <strong>公告通知:</strong> 禁止上传黄、赌、毒违规文件，后果自负！ 
        </div>
    </div>
</body>
</html>

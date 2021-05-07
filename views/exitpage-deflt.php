<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $t("maintenance-page-title") ?>
    </title>
    <style>
        body,
        html {
            margin: 0;
            padding: 0;
        }

        p {
            text-align: center;
            font-family: Arial, 'helvetica neue', helvetica, sans-serif;
            margin: 10vh 10vw;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <p>&mdash; <?= $t("maintenance-page-msg") ?>
    </p>
</body>

</html>
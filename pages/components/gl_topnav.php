<header>
    <!-- Fixed navbar -->
    <nav class="navbar navbar-expand-md navbar-dark fixed-top">
        <div class="container pt-3 pb-3">
            <a class="navbar-brand p-0 m-0" href="<?=$module->getUrl('pages/dashboard.php')?>" title="HeartEx">HeartEx®</a>
            <?php
            if(!in_array($page, array("login_reg","help","password_reset"))){
                ?>
                <ul class="nav justify-content-center align-baseline">
                    <li class="nav-item"><a class="nav-link active" href="<?=$module->getUrl('pages/dashboard.php')?>">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?=$module->getUrl('pages/tree_view.php')?>">Prescription Trees</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?=$module->getUrl('pages/help.php')?>">Support</a></li>
                </ul>
                <?php
                    $provider_full_name = $_SESSION["logged_in_user"]["provider_fname"] . " " . $_SESSION["logged_in_user"]["provider_mname"] . " " . $_SESSION["logged_in_user"]["provider_lname"];
                ?>
                <div class="dropdown align-baseline">
                    <button class="btn btn-secondary dropdown-toggle border-0" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?=$provider_full_name?></button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <a class="dropdown-item" href="<?=$module->getUrl('pages/login.php')?>">Logout</a>
                    </div>
                </div>
                <?php
            }else{
                ?>
                <div class="dropdown align-baseline text-white">
                    <a class="text-white" href="<?=$module->getUrl('pages/login.php')?>">Login</a> | <a class="text-white"  href="<?=$module->getUrl('pages/registration.php')?>">Register</a>
                </div>
                <?php
            }
            ?>
        </div>
    </nav>
</header>
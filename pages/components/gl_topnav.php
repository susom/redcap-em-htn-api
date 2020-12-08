<style>
.nav-link.active{
    color:#CDB03B;
}
</style>
<header>
    <!-- Fixed navbar -->
    <nav class="navbar navbar-expand-md navbar-dark fixed-top">
        <div class="container pt-3 pb-3">
            <a class="navbar-brand p-0 m-0" href="<?=$module->getUrl('pages/dashboard.php')?>" title="HeartEx">HeartEx®</a>
            <?php
            if(!empty($_SESSION["logged_in_user"])){
                ?>
                <ul class="nav justify-content-center align-baseline">
                    <li class="nav-item"><a class="nav-link <?= $home_active ?> home" href="<?=$module->getUrl('pages/dashboard.php')?>">Home</a></li>
                    <li class="nav-item"><a class="nav-link <?= $tree_active ?> ptree" href="<?=$module->getUrl('pages/tree_view.php')?>">Tree Templates</a></li>
                    <li class="nav-item"><a class="nav-link <?= $help_active ?> help" href="<?=$module->getUrl('pages/help.php')?>">Support</a></li>
                </ul>
                <?php
                    $provider_full_name = $_SESSION["logged_in_user"]["provider_fname"] . " " . $_SESSION["logged_in_user"]["provider_mname"] . " " . $_SESSION["logged_in_user"]["provider_lname"];
                ?>
                <div class="dropdown align-baseline">
                    <button class="btn btn-secondary dropdown-toggle border-0" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?=$provider_full_name?></button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <a class="dropdown-item" href="<?=$module->getUrl('pages/provider.php',true,true)?>">Profile</a>
                        <a class="dropdown-item" href="<?=$module->getUrl('pages/login.php',true,true)?>&logout=1">Logout</a>
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
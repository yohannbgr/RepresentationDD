<nav>
    <div class="nav-inner">
        <a class="nav-logo" href="index.php">WHO<span>Surveillance</span></a>
        <ul>
            <li><a href="index.php" <?= basename($_SERVER['PHP_SELF'])=='index.php'?'class="active"':'' ?>>Dashboard</a></li>
            <li><a href="pays.php" <?= basename($_SERVER['PHP_SELF'])=='pays.php'?'class="active"':'' ?>>Pays</a></li>
            <li><a href="maladie.php" <?= basename($_SERVER['PHP_SELF'])=='maladie.php'?'class="active"':'' ?>>Maladies</a></li>
            <li><a href="recherche.php" <?= basename($_SERVER['PHP_SELF'])=='recherche.php'?'class="active"':'' ?>>Recherche</a></li>
            <li><a href="stats.php" <?= basename($_SERVER['PHP_SELF'])=='stats.php'?'class="active"':'' ?>>Requêtes SQL</a></li>
        </ul>
    </div>
</nav>

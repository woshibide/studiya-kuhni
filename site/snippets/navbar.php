<nav>
    <div class="nav-left">
        <div class="nav-menu" data-nav-menu>
            <div class="nav-menu-toggle-shell hover-bg">
                <button
                    class="nav-menu-toggle hover-underline"
                    type="button"
                    aria-expanded="false"
                    aria-controls="nav-menu-panel"
                    data-open-nav-menu
                    >
                    меню
                </button>
            </div>
            <?php snippet('nav-menu-panel') ?>
        </div>
    </div>

    <div class="nav-center">
        <a  
            class="hover-underline" 
            <?= esc(relative_url('/'), 'attr') ?>"
            >
            Студия Кухни
        </a>
    </div>

    <div class="nav-right" data-nav-contact>
        <div class="nav-contact-toggle-shell hover-bg">
            <button
                class="nav-contact-toggle hover-underline"
                type="button"
                aria-expanded="false"
                aria-controls="nav-contact-panel"
                >
                контакты
            </button>
        </div>
        <?php snippet('nav-contact-panel') ?>
    </div>
</nav>

const MagefanMenuManager = {
    // Configuration
    config: {
        menuSelector: '#menu-magefan-community-elements',
        defaultBgColor: '#4a4542',
        searchThreshold: 16,
        menuOffsetHeight: 147,
        menuLeftPosition: '88px'
    },

    // Cache DOM elements
    elements: {},

    /**
     * Initialize the menu manager
     */
    init() {
        this.elements.menu = document.querySelector(this.config.menuSelector);

        if (!this.elements.menu) {
            return;
        }

        this.cacheElements();
        this.setupMenu();
    },

    /**
     * Cache frequently used DOM elements
     */
    cacheElements() {
        this.elements.bgSubmenu = document.querySelector('.admin__menu .level-0 > .submenu');
        this.elements.level1Parents = this.elements.menu.querySelectorAll('.level-1.parent');
        this.elements.submenuContainer = this.elements.menu.querySelector('.level-0 > .submenu > ul[role="menu"]');
        this.elements.closeButton = this.elements.menu.querySelector('.action-close');
    },

    /**
     * Get background color for submenu
     */
    getSubmenuBgColor() {
        if (this.elements.bgSubmenu) {
            return window.getComputedStyle(this.elements.bgSubmenu).backgroundColor;
        }
        return this.config.defaultBgColor;
    },

    /**
     * Setup the entire menu
     */
    setupMenu() {
        const bgColor = this.getSubmenuBgColor();

        this.processLevel1Parents(bgColor);
        this.reorganizeSubmenu();
        this.setupMenuObserver();
    },

    /**
     * Process all level-1 parent items
     */
    processLevel1Parents(bgColor) {
        this.elements.level1Parents.forEach(parent => {
            const submenu = parent.querySelector('.submenu');
            const groupTitleSpan = parent.querySelector('.submenu-group-title span');

            if (!groupTitleSpan || !submenu) {
                return;
            }

            // Set background color
            submenu.style.backgroundColor = bgColor;

            // Skip if already processed
            if (submenu.querySelector('.submenu-item-title')) {
                return;
            }

            const titleText = groupTitleSpan.textContent.trim();

            this.addSubmenuHeader(submenu, titleText);
            this.attachSubmenuEvents(parent, submenu);
        });
    },

    /**
     * Add header elements to submenu
     */
    addSubmenuHeader(submenu, titleText) {
        // Create title
        const itemTitle = document.createElement('strong');
        itemTitle.className = 'submenu-item-title';
        itemTitle.textContent = titleText;

        // Create close button
        const closeBtn = document.createElement('a');
        closeBtn.href = '#';
        closeBtn.className = 'action-close-submenu';

        // Prepend elements
        submenu.prepend(closeBtn);
        submenu.prepend(itemTitle);

        return closeBtn;
    },

    /**
     * Attach event listeners to submenu
     */
    attachSubmenuEvents(parent, submenu) {
        const closeBtn = submenu.querySelector('.action-close-submenu');

        // Close button click
        closeBtn?.addEventListener('click', (e) => {
            e.preventDefault();

            setTimeout(function () {
                MagefanMenuManager.closeActiveSubmenus();
                MagefanMenuManager.closeActiveLevel1menus();
            }, 50)
        });

        // Parent click to open submenu
        parent.addEventListener('click', () => {
            this.toggleSubmenu(parent);
        });
    },

    /**
     * Toggle submenu visibility
     */
    toggleSubmenu(parent) {
        let wasOpened = parent.classList.contains('active');

        this.closeActiveLevel1menus();
        this.closeActiveSubmenus();

        if (wasOpened) {
            return;
        }

        parent.classList.add('active');

        const submenu = parent.querySelector('.submenu');
        if (submenu) {
            submenu.classList.add('_show');
        }
    },

    /**
     * Reorganize submenu structure
     */
    reorganizeSubmenu() {
        if (!this.elements.submenuContainer) {
            return;
        }

        const allLevel1Parents = Array.from(
            this.elements.menu.querySelectorAll('.level-0 > .submenu .parent.level-1')
        );

        // Clear container and re-add items
        this.elements.submenuContainer.innerHTML = '';
        allLevel1Parents.forEach(parent => {
            this.elements.submenuContainer.appendChild(parent);
        });

        // Sort items alphabetically
        this.sortSubmenuItems();

        // Add search if threshold met
        if (allLevel1Parents.length > this.config.searchThreshold) {
            this.addSearchFunctionality(allLevel1Parents);
        }
    },

    /**
     * Sort submenu items alphabetically
     */
    sortSubmenuItems() {
        const items = Array.from(this.elements.submenuContainer.querySelectorAll('.parent.level-1'));

        items.sort((a, b) => {
            const textA = (a.querySelector('.submenu-group-title span')?.textContent || '').trim();
            const textB = (b.querySelector('.submenu-group-title span')?.textContent || '').trim();
            return textA.localeCompare(textB);
        });

        items.forEach(item => this.elements.submenuContainer.appendChild(item));
    },

    /**
     * Add search functionality
     */
    addSearchFunctionality(items) {
        const searchContainer = this.createSearchContainer();
        this.elements.submenuContainer.parentElement.insertBefore(
            searchContainer,
            this.elements.submenuContainer
        );

        const searchInput = searchContainer.querySelector('.mf-menu-search-input');
        const searchClose = searchContainer.querySelector('.action-search-close');

        this.attachSearchEvents(searchInput, searchClose, items);
    },

    /**
     * Create search container with input and close button
     */
    createSearchContainer() {
        const container = document.createElement('div');
        container.className = 'mf-menu-search-container';

        const input = document.createElement('input');
        input.type = 'text';
        input.placeholder = 'Search in the menu...';
        input.className = 'mf-menu-search-input';

        const closeBtn = document.createElement('div');
        closeBtn.className = 'action-search-close';

        container.appendChild(input);
        container.appendChild(closeBtn);

        return container;
    },

    /**
     * Attach search event listeners
     */
    attachSearchEvents(searchInput, searchClose, items) {
        searchInput.addEventListener('input', () => {
            this.handleSearch(searchInput, searchClose, items);
        });

        searchClose.addEventListener('click', () => {
            this.clearSearch(searchInput, searchClose, items);
        });
    },

    /**
     * Handle search input
     */
    handleSearch(searchInput, searchClose, items) {
        const searchText = searchInput.value.toLowerCase().trim();

        // Toggle close button visibility
        searchClose.classList.toggle('_show', searchText.length > 0);

        items.forEach(item => {
            const titleSpan = item.querySelector('.submenu-group-title span');
            if (!titleSpan) return;

            const titleText = titleSpan.textContent.toLowerCase();
            const isMatch = searchText === '' || titleText.includes(searchText);

            // Show/hide item
            item.style.display = isMatch ? '' : 'none';

            // Highlight matching text
            this.highlightText(titleSpan, searchText, isMatch);
        });
    },

    /**
     * Highlight matching text
     */
    highlightText(titleSpan, searchText, isMatch) {
        const originalText = titleSpan.getAttribute('data-original-text') || titleSpan.textContent;

        // Store original text on first search
        if (!titleSpan.hasAttribute('data-original-text')) {
            titleSpan.setAttribute('data-original-text', originalText);
        }

        if (searchText !== '' && isMatch) {
            const regex = new RegExp(`(${this.escapeRegex(searchText)})`, 'gi');
            titleSpan.innerHTML = originalText.replace(
                regex,
                '<mark style="background: #eb5202;">$1</mark>'
            );
        } else {
            titleSpan.textContent = originalText;
        }
    },

    /**
     * Escape special regex characters
     */
    escapeRegex(text) {
        return text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    },

    /**
     * Clear search and reset items
     */
    clearSearch(searchInput, searchClose, items) {
        searchInput.value = '';
        searchClose.classList.remove('_show');

        items.forEach(item => {
            item.style.display = '';

            const titleSpan = item.querySelector('.submenu-group-title span');
            if (titleSpan) {
                const originalText = titleSpan.getAttribute('data-original-text');
                if (originalText) {
                    titleSpan.textContent = originalText;
                }
            }
        });

        searchInput.focus();
    },

    /**
     * Setup mutation observer for menu state changes
     */
    setupMenuObserver() {
        const observer = new MutationObserver(() => {
            this.handleMenuStateChange();
        });

        observer.observe(this.elements.menu, {
            attributes: true,
            attributeFilter: ['class']
        });
    },

    /**
     * Handle menu state changes
     */
    handleMenuStateChange() {
        const isMenuOpen = this.elements.menu.classList.contains('_show');
        const submenuDiv = this.elements.menu.querySelector('.submenu');
        const submenuList = this.elements.menu.querySelector('.submenu > ul[role="menu"]');

        if (!isMenuOpen) {
            this.closeActiveSubmenus();
            this.closeActiveLevel1menus();
            document.body.style.overflowY = 'auto';
            return;
        }

        if (submenuDiv && submenuList) {
            this.adjustSubmenuPosition(submenuDiv, submenuList);
            document.body.style.overflowY = 'hidden';
        }
    },

    /**
     *  Close all active level1 menus
     */
    closeActiveLevel1menus() {
        this.elements.level1Parents.forEach(parent => {
            parent.classList.remove('active');
        });
    },

    /**
     * Close all active submenus
     */
    closeActiveSubmenus() {
        const activeSubmenu = this.elements.menu.querySelector('.level-1.parent.active .submenu._show');
        if (activeSubmenu) {
            activeSubmenu.classList.remove('_show');
        }
    },

    /**
     * Adjust submenu position and height
     */
    adjustSubmenuPosition(submenuDiv, submenuList) {
        const searchContainer = this.elements.menu.querySelector('.mf-menu-search-container');
        const searchHeight = searchContainer ? searchContainer.offsetHeight : 0;

        const maxHeight = document.documentElement.clientHeight - this.config.menuOffsetHeight - searchHeight;

        submenuList.style.maxHeight = `${maxHeight}px`;
        submenuDiv.style.position = 'fixed';
        submenuDiv.style.left = this.config.menuLeftPosition;
    }
};

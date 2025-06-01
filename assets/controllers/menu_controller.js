import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static targets = ['menu', 'overlay'];

    connect() {
        // Initialize the menu in closed state
        this.closeMenu();
    }

    toggleMenu(event) {
        event.preventDefault();
        
        const menuElement = this.menuTarget;
        const overlayElement = this.overlayTarget;
        const hamburgerIcon = event.currentTarget;
        
        if (menuElement.classList.contains('active')) {
            this.closeMenu();
        } else {
            menuElement.classList.add('active');
            overlayElement.classList.add('active');
            hamburgerIcon.classList.add('active');
        }
    }

    closeMenu() {
        const menuElement = this.menuTarget;
        const overlayElement = this.overlayTarget;
        const hamburgerIcon = document.querySelector('.hamburger-icon');
        
        menuElement.classList.remove('active');
        overlayElement.classList.remove('active');
        if (hamburgerIcon) {
            hamburgerIcon.classList.remove('active');
        }
    }
}
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
// Import French locale
import { French } from 'flatpickr/dist/l10n/fr.js';

class DatePickerComponent {
    constructor() {
        this.instances = new Map();
        this.config = {
            dateFormat: 'Y-m-d',
            allowInput: true,
            altInput: true,
            altFormat: 'd/m/Y',
            locale: this.getValidLocale(),
            clickOpens: true,
            allowInvalidPreload: false,
            wrap: false,
        };
        
        // Initialize on DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.initializeAll());
        } else {
            this.initializeAll();
        }
        
        // Set up mutation observer for dynamically added content
        this.setupMutationObserver();
        
        // Listen for AJAX content updates
        this.setupAjaxListeners();
    }
    
    /**
     * Get a valid locale for Flatpickr
     */
    getValidLocale() {
        const documentLang = document.documentElement.lang || 'en';
        const supportedLocales = {
            'fr': French,
            'en': 'default'
        };
        
        return supportedLocales[documentLang] || 'default';
    }
    
    /**
     * Format date to ISO string without timezone conversion
     * This avoids the timezone shift bug caused by toISOString()
     */
    formatDateToIso(date) {
        if (!date) return '';
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    }
    
    /**
     * Initialize all date input fields on the page
     */
    initializeAll() {
        const dateInputs = this.findDateInputs();
        dateInputs.forEach(input => this.initialize(input));
    }
    
    /**
     * Find all date input fields using multiple selectors
     */
    findDateInputs() {
        const selectors = [
            'input[name*="date"]',
            'input[data-datepicker]',
            'input.date-input',
            'input[name="due_date"]',
            'input[name="done_date"]',
            'input[name="event_date"]'
        ];
        
        return Array.from(document.querySelectorAll(selectors.join(', ')))
            .filter(input => input.type === 'text' && !this.instances.has(input));
    }
    
    /**
     * Initialize a single date input field
     */
    initialize(input) {
        if (this.instances.has(input)) {
            return; // Already initialized
        }
        
        // Configure based on input attributes
        const config = { 
            ...this.config,
            // Add onChange handler to integrate with existing app functionality
            onChange: (selectedDates, dateStr, instance) => {
                // Trigger native change event for existing app handlers (noformat)
                const changeEvent = new Event('change', { bubbles: true });
                input.dispatchEvent(changeEvent);
                
                // Trigger input event for styling
                const inputEvent = new Event('input', { bubbles: true });
                input.dispatchEvent(inputEvent);
            }
        };
        
        // Check for existing value and convert if needed
        if (input.value) {
            const parsedDate = this.parseExistingDate(input.value);
            if (parsedDate) {
                input.value = this.formatDateToIso(parsedDate);
            }
        }
        
        // Set placeholder to indicate expected format
        if (!input.placeholder || input.placeholder.includes('xx/xx/yyyy')) {
            input.placeholder = 'dd/mm/yyyy';
        }
        
        // Add noformat class for compatibility with existing app handlers
        if (!input.classList.contains('noformat')) {
            input.classList.add('noformat');
        }
        
        // Initialize Flatpickr
        const instance = flatpickr(input, config);
        this.instances.set(input, instance);
        
        // Add CSS class for styling
        input.classList.add('date-picker-enabled');
        
        // Set up form submission handler to ensure ISO format
        this.setupFormSubmissionHandler(input);
        
        return instance;
    }
    
    /**
     * Parse existing date value that might be in locale format
     */
    parseExistingDate(dateString) {
        if (!dateString || dateString.trim() === '') {
            return null;
        }
        
        // Try ISO format first (YYYY-MM-DD)
        const isoMatch = dateString.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (isoMatch) {
            return new Date(dateString);
        }
        
        // Try common locale formats
        const formats = [
            /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/, // DD/MM/YYYY or MM/DD/YYYY
            /^(\d{1,2})-(\d{1,2})-(\d{4})$/, // DD-MM-YYYY or MM-DD-YYYY
            /^(\d{1,2})\.(\d{1,2})\.(\d{4})$/, // DD.MM.YYYY
        ];
        
        for (const format of formats) {
            const match = dateString.match(format);
            if (match) {
                const [, part1, part2, year] = match;
                // Assume DD/MM/YYYY format (European style)
                const day = parseInt(part1, 10);
                const month = parseInt(part2, 10);
                const yearNum = parseInt(year, 10);
                
                if (day <= 12 && month <= 12) {
                    // Ambiguous - use DD/MM/YYYY as default
                    return new Date(yearNum, month - 1, day);
                } else if (day > 12) {
                    // Must be DD/MM/YYYY
                    return new Date(yearNum, month - 1, day);
                } else if (month > 12) {
                    // Must be MM/DD/YYYY
                    return new Date(yearNum, part1 - 1, part2);
                }
            }
        }
        
        // Fallback to native parsing
        try {
            return new Date(dateString);
        } catch (e) {
            console.warn('Could not parse date:', dateString);
            return null;
        }
    }
    
    /**
     * Set up form submission handler to ensure ISO format is submitted
     */
    setupFormSubmissionHandler(input) {
        const form = input.closest('form');
        if (!form) return;
        
        // Create hidden input for ISO format
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = input.name + '_iso';
        form.appendChild(hiddenInput);
        
        // Update hidden input when date changes
        const updateHiddenInput = () => {
            const instance = this.instances.get(input);
            if (instance && instance.selectedDates.length > 0) {
                hiddenInput.value = this.formatDateToIso(instance.selectedDates[0]);
            } else {
                hiddenInput.value = '';
            }
        };
        
        input.addEventListener('change', updateHiddenInput);
        updateHiddenInput(); // Initial update
    }
    
    /**
     * Destroy a date picker instance
     */
    destroy(input) {
        const instance = this.instances.get(input);
        if (instance) {
            instance.destroy();
            this.instances.delete(input);
            input.classList.remove('date-picker-enabled');
        }
    }
    
    /**
     * Destroy all date picker instances
     */
    destroyAll() {
        this.instances.forEach((instance, input) => {
            this.destroy(input);
        });
    }
    
    /**
     * Reinitialize date pickers after AJAX content update
     */
    reinitialize() {
        // Find new date inputs and initialize them
        this.initializeAll();
    }
    
    /**
     * Set up mutation observer to detect dynamically added content
     */
    setupMutationObserver() {
        const observer = new MutationObserver((mutations) => {
            let shouldReinitialize = false;
            
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            // Check if the node or its children contain date inputs
                            const hasDateInputs = node.matches && node.matches('input[name*="date"]') ||
                                                node.querySelector && node.querySelector('input[name*="date"]');
                            
                            if (hasDateInputs) {
                                shouldReinitialize = true;
                            }
                        }
                    });
                }
            });
            
            if (shouldReinitialize) {
                // Debounce reinitializtion to avoid multiple calls
                clearTimeout(this.reinitTimeout);
                this.reinitTimeout = setTimeout(() => this.reinitialize(), 100);
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        this.mutationObserver = observer;
    }
    
    /**
     * Set up listeners for AJAX content updates
     */
    setupAjaxListeners() {
        // Listen for Bootstrap modal events
        document.addEventListener('shown.bs.modal', () => {
            setTimeout(() => this.initializeAll(), 50);
        });
        
        // Listen for custom AJAX events
        document.addEventListener('ajaxContentLoaded', () => {
            this.initializeAll();
        });
        
        // Hook into existing fetchInto function if available
        if (window.fetchInto) {
            const originalFetchInto = window.fetchInto;
            window.fetchInto = async (url, element) => {
                const result = await originalFetchInto(url, element);
                setTimeout(() => this.initializeAll(), 50);
                return result;
            };
        }
    }
    
    /**
     * Get formatted date value in ISO format
     */
    getISOValue(input) {
        const instance = this.instances.get(input);
        if (instance && instance.selectedDates.length > 0) {
            return instance.selectedDates[0].toISOString().split('T')[0];
        }
        return '';
    }
    
    /**
     * Set date value programmatically
     */
    setDate(input, dateValue) {
        const instance = this.instances.get(input);
        if (instance) {
            instance.setDate(dateValue);
        }
    }
    
    /**
     * Clear date value
     */
    clear(input) {
        const instance = this.instances.get(input);
        if (instance) {
            instance.clear();
        }
    }
}

// Create global instance
const datePickerComponent = new DatePickerComponent();

// Export for module usage
export default DatePickerComponent;

// Make available globally for legacy code
window.DatePicker = datePickerComponent;
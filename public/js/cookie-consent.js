document.addEventListener('DOMContentLoaded', function () {
    // Grab references to the elements in the DOM
    const banner = document.getElementById('ev-cookie-consent-banner');
    const configurator = document.getElementById('ev-cookie-configurator');
    const acceptAllBtn = document.getElementById('ev-cookie-accept-all');
    const rejectAllBtn = document.getElementById('ev-cookie-reject-all');
    const configureBtn = document.getElementById('ev-cookie-configure');
    const saveBtn = document.getElementById('ev-cookie-save-consent');
    const backBtn = document.getElementById('ev-cookie-back');
    const retractBtn = document.getElementById('ev-cookie-retract-consent');
    const retractContainer = document.getElementById('ev-cookie-retract-consent-container');
    const retractInfo = document.getElementById('ev-cookie-retract-info');
    const consentDateTimeEl = document.getElementById('ev-consent-datetime');
    const consentExpirationEl = document.getElementById('ev-consent-expiration');
    const consentUuidEl = document.getElementById('ev-consent-uuid');
    const consentVersionEl = document.getElementById('ev-consent-version');

    // Initialize the cookie consent system, apply the correct theme
    initializeCookieConsent();
    
    // Event listener for the "Accept All" button
    if(acceptAllBtn) {
        acceptAllBtn.addEventListener('click', function () {
            let consentData = {};
            document.querySelectorAll('#ev-cookie-consent-form input').forEach(function (input) {
                if (!input.disabled) {
                    consentData[input.name] = true; // Mark all categories as accepted
                }
            });
            sendCookieConsent(consentData); // Send consent data to the server
        });
    }

    // Event listener for the "Reject All" button
    if(rejectAllBtn) {
        rejectAllBtn.addEventListener('click', function () {
            let consentData = {};
            document.querySelectorAll('#ev-cookie-consent-form input').forEach(function (input) {
                if (!input.disabled) {
                    consentData[input.name] = false; // Mark all categories as rejected
                }
            });
            sendCookieConsent(consentData); // Send consent data to the server
        });
    }

    // Event listener for the "Configure" button
    if(configureBtn) {
        configureBtn.addEventListener('click', function () {
            banner.style.display = 'none'; // Hide the banner
            configurator.style.display = 'block'; // Show the configurator
        });
    }

    // Event listener for the "Save Preferences" button in the configurator
    if(saveBtn) {
        saveBtn.addEventListener('click', function () {
            let consentData = {};
            document.querySelectorAll('#ev-cookie-consent-form input').forEach(function (input) {
                consentData[input.name] = input.checked; // Capture the user's preferences
            });
            sendCookieConsent(consentData); // Send consent data to the server
        });
    }

    // Event listener for the "Back" button in the configurator
    if(backBtn) {
        backBtn.addEventListener('click', function () {
            configurator.style.display = 'none'; // Hide the configurator
            banner.style.display = 'block'; // Show the banner again
        });
    }

    // Event listener for the "Retract Consent" button
    if(retractBtn) {
        retractBtn.addEventListener('click', function () {
            retractCookieConsent(); // Handle retraction of consent
        });
    }

    // Initialize the retract button with a plus sign, and add hover behavior
    if (retractBtn && retractInfo) {
        retractBtn.setAttribute('data-text', retractBtn.textContent.trim());
        retractBtn.textContent = ''; // Replace button text with plus sign
        
        retractBtn.addEventListener('mouseenter', function () {
            retractBtn.classList.add('expanded'); // Expand the button on hover
            retractInfo.style.display = 'block';
        });

        retractBtn.addEventListener('mouseleave', function () {
            retractBtn.classList.remove('expanded'); // Shrink the button when not hovered
            retractInfo.style.display = 'none';
        });

        retractBtn.addEventListener('click', function () {
            retractCookieConsent(); // Handle retraction of consent
        });
    }

    // Function to send the user's consent data to the server
    function sendCookieConsent(consentData) {
        let xhr = new XMLHttpRequest();
        xhr.open('POST', cookieConsentUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                banner.style.display = 'none'; // Hide the banner after consent is given
                configurator.style.display = 'none'; // Hide the configurator after consent is given
                retractContainer.style.display = 'block'; // Show the retract button

                let responseJson = JSON.parse(xhr.responseText);
                let cookieValue = JSON.parse(responseJson.cookieValue);
                consentDateTimeEl.textContent = cookieValue.datetime;
                consentExpirationEl.textContent = cookieValue.expiration;
                consentUuidEl.textContent = cookieValue.uuid;
                consentVersionEl.textContent = cookieValue.version;
                
                // Rebuild the list with updated categories
                for (const [category, accepted] of Object.entries(cookieValue.consentData)) {
                    const categoryElement = document.getElementById(`ev-consent-categories-list-${category}`);
                    if(categoryElement){
                        if (categoryElement) {
                            categoryElement.innerHTML = (accepted === 'true')
                                ? '<span class="ev-accepted">' + category_accepted + '</span>' 
                                : '<span class="ev-rejected">' + category_rejected + '</span>';
                        }
                    } else {
                        // Dynamically create the <li> element since it doesn't exist
                        const newLi = document.createElement('li');
                        const capitalizeSpan = document.createElement('span');
                        capitalizeSpan.classList.add('ev-capitalize');
                        capitalizeSpan.textContent = categoryNames[`category_name_${category}`];
                        const statusSpan = document.createElement('span');
                        statusSpan.id = `ev-consent-categories-list-${category}`;
                        statusSpan.innerHTML = (accepted === 'true')
                            ? '<span class="ev-accepted">' + category_accepted + '</span>' 
                            : '<span class="ev-rejected">' + category_rejected + '</span>';
                        newLi.appendChild(capitalizeSpan);
                        newLi.appendChild(document.createTextNode(': '));
                        newLi.appendChild(statusSpan);

                        document.querySelector('#ev-consent-categories-ul').appendChild(newLi);
                    }
                }
            }
        };
        xhr.send(new URLSearchParams(consentData).toString()); // Send the consent data
    }

    // Function to handle retraction of consent
    function retractCookieConsent() {
        let xhr = new XMLHttpRequest();
        xhr.open('POST', retractConsentUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                banner.style.display = 'block'; // Show the banner again
                configurator.style.display = 'none'; // Hide the configurator
                retractContainer.style.display = 'none'; // Hide the retract button
            }
        };
        xhr.send(); // Send the retraction request
    }

    // Function to apply dark mode to the cookie consent elements
    function applyDarkMode() {
        const banner = document.getElementById('ev-cookie-consent-banner');
        const configurator = document.getElementById('ev-cookie-configurator');
        const retract = document.getElementById('ev-cookie-retract-info');
        const buttons = document.querySelectorAll('.light-mode-button');
        
        banner.classList.remove('light-mode-banner');
        banner.classList.add('dark-mode-banner');
        
        configurator.classList.remove('light-mode-banner');
        configurator.classList.add('dark-mode-banner');
        
        retract.classList.remove('light-mode-banner');
        retract.classList.add('dark-mode-banner');
        
        buttons.forEach(button => {
            button.classList.remove('light-mode-button');
            button.classList.add('dark-mode-button');
        });
    }

    // Function to initialize the cookie consent system based on the selected theme mode
    function initializeCookieConsent() {
        if (themeMode === 'dark') {
            applyDarkMode(); // Apply dark mode if selected
        } else if (themeMode === 'light') {
            // Do nothing. Light mode is applied by default
        } else if (themeMode === 'auto') {
            const isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (isDarkMode) {
                applyDarkMode(); // Apply dark mode if the system prefers it
            }
        }
    }
});

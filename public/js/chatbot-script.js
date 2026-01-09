/**
 * Frontend Chatbot JavaScript
 *
 * @package Questify
 * @since 1.0.0
 */

(function($) {
    'use strict';

    class WpFaqChat {
        constructor() {
            this.sessionId = this.generateSessionId();
            this.messageHistory = [];
            this.isOpen = false;
            this.currentFaqId = null;
            this.settings = questiData.settings;

            this.init();
        }

        init() {
            this.createChatUI();
            this.bindEvents();

            // Bei "auto"-Modus: LocalStorage löschen und neu starten
            if (this.settings.historyMode === 'auto') {
                localStorage.removeItem('chatbot_history');
                this.messageHistory = [];
                // Im Auto-Modus startet der Chat immer leer
            } else {
                // Bei "manual"-Modus: Aus LocalStorage laden
                this.loadFromStorage();
            }
        }

        generateSessionId() {
            let sessionId = localStorage.getItem('chatbot_session_id');
            if (!sessionId) {
                sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                localStorage.setItem('chatbot_session_id', sessionId);
            }
            return sessionId;
        }

        createChatUI() {
            const position = this.settings.position || 'right';
            const size = this.settings.size || 'medium';
            const buttonText = this.settings.buttonText || 'Fragen?';
            const title = this.settings.title || 'FAQ Chatbot';

            const html = `
                <button id="chatbot-button" class="${position}">
                    <span>💬</span>
                    <span>${buttonText}</span>
                </button>

                <div id="chatbot-widget" class="hidden ${position} ${size}">
                    <div id="chatbot-header">
                        <h3>${title}</h3>
                        <div id="chatbot-header-controls">
                            ${this.settings.historyMode === 'manual' ? '<button id="chatbot-clear" title="Chat löschen">🗑️</button>' : ''}
                            <button id="chatbot-minimize" title="${this.settings.strings.minimize}">−</button>
                            <button id="chatbot-close" title="${this.settings.strings.close}">×</button>
                        </div>
                    </div>

                    <div id="chatbot-messages"></div>

                    <div id="chatbot-contact-form" class="hidden"></div>

                    <div id="chatbot-input-area">
                        <textarea id="chatbot-input"
                                  placeholder="${this.settings.placeholderText}"
                                  rows="1"></textarea>
                        <button id="chatbot-send-button">
                            <span>➤</span>
                        </button>
                    </div>
                </div>
            `;

            $('#chatbot-container').html(html);
        }

        bindEvents() {
            const self = this;

            // Open Chat
            $(document).on('click', '#chatbot-button', function() {
                self.openChat();
            });

            // Close Chat
            $(document).on('click', '#chatbot-close', function() {
                self.closeChat();
            });

            // Minimize Chat
            $(document).on('click', '#chatbot-minimize', function() {
                self.closeChat();
            });

            // Clear Chat
            $(document).on('click', '#chatbot-clear', function() {
                if (confirm('Möchten Sie den Chat-Verlauf wirklich löschen?')) {
                    self.clearChat();
                }
            });

            // Send Message
            $(document).on('click', '#chatbot-send-button', function() {
                self.sendMessage();
            });

            // Enter to send (Shift+Enter for new line)
            $(document).on('keydown', '#chatbot-input', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    self.sendMessage();
                }
            });

            // Helpful buttons
            $(document).on('click', '.chatbot-helpful-btn', function() {
                const helpful = $(this).data('helpful');
                const faqId = $(this).data('faq-id');
                self.rateAnswer(faqId, helpful);
                $(this).parent().html('<small style="color: #999;">Danke für Ihr Feedback!</small>');
            });

            // Show contact form
            $(document).on('click', '.chatbot-show-contact', function() {
                self.showContactForm();
            });

            // Disambiguation option clicked
            $(document).on('click', '.chatbot-disambiguation-option', function() {
                const faqId = $(this).data('faq-id');
                self.selectDisambiguationOption(faqId);
            });

            // Submit contact form
            $(document).on('click', '#chatbot-contact-submit', function() {
                self.submitContactForm();
            });

            // Cancel contact form
            $(document).on('click', '#chatbot-contact-cancel', function() {
                self.hideContactForm();
            });

            // Online/Offline detection
            window.addEventListener('online', function() {
                $('.chatbot-offline-message').remove();
            });

            window.addEventListener('offline', function() {
                self.showOfflineMessage();
            });
        }

        openChat() {
            $('#chatbot-button').addClass('hidden');
            $('#chatbot-widget').removeClass('hidden').addClass('opening');

            setTimeout(() => {
                $('#chatbot-widget').removeClass('opening');
            }, 300);

            // Prüfen ob Chat-Messages-Container bereits Inhalte hat
            const hasMessages = $('#chatbot-messages').children().length > 0;

            // Wenn keine Nachrichten im DOM vorhanden
            if (!hasMessages) {
                if (this.messageHistory.length === 0) {
                    // Keine Historie: Begrüßungsnachricht anzeigen
                    this.addBotMessage(this.settings.welcomeMessage);
                    if (this.settings.historyMode === 'manual') {
                        this.saveToStorage();
                    }
                } else {
                    // Historie vorhanden: Rendern
                    this.renderHistory();
                }
            }

            $('#chatbot-input').focus();
            this.isOpen = true;
            this.scrollToBottom();
        }

        closeChat() {
            $('#chatbot-widget').addClass('closing');

            setTimeout(() => {
                $('#chatbot-widget').addClass('hidden').removeClass('closing');
                $('#chatbot-button').removeClass('hidden');
            }, 300);

            this.isOpen = false;
        }

        sendMessage() {
            const input = $('#chatbot-input');
            const question = input.val().trim();

            if (!question) return;

            // Add user message
            this.addUserMessage(question);
            input.val('');

            // Show typing indicator
            this.showTyping();

            // AJAX request
            $.post(questiData.ajaxurl, {
                action: 'questi_get_answer',
                nonce: questiData.nonce,
                question: question,
                session_id: this.sessionId
            }, (response) => {
                this.hideTyping();

                if (response.success) {
                    const data = response.data;

                    if (data.found) {
                        // Check if disambiguation is needed
                        if (data.needs_disambiguation && data.options) {
                            // Show disambiguation options
                            this.showDisambiguationOptions(data.disambiguation_message, data.options);
                        } else {
                            // Normal answer found
                            this.addBotMessage(data.answer, data.faq_id);
                            this.currentFaqId = data.faq_id;
                        }
                    } else {
                        // No answer found or low confidence
                        this.addBotMessage(data.message);
                        // Show contact form button (für low_confidence oder keine Antwort)
                        if (data.show_contact || data.low_confidence || !data.found) {
                            this.showContactFormButton();
                        }
                    }
                } else {
                    this.addBotMessage('Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.');
                }

                if (this.settings.historyMode === 'manual') {
                    this.saveToStorage();
                }
            }).fail(() => {
                this.hideTyping();
                this.addBotMessage('Verbindungsfehler. Bitte prüfen Sie Ihre Internetverbindung.');
            });
        }

        addUserMessage(text) {
            const html = `
                <div class="chatbot-message user">
                    <div class="chatbot-message-content">${this.escapeHtml(text)}</div>
                </div>
            `;
            $('#chatbot-messages').append(html);
            this.messageHistory.push({ type: 'user', text: text });
            this.scrollToBottom();
        }

        addBotMessage(text, faqId = null) {
            let html = `
                <div class="chatbot-message bot">
                    <div class="chatbot-message-content">
                        ${text}
            `;

            if (faqId) {
                html += `
                    <div class="chatbot-helpful-buttons">
                        <small>${this.settings.strings.helpful}</small>
                        <button class="chatbot-helpful-btn" data-helpful="yes" data-faq-id="${faqId}">
                            ${this.settings.strings.yes}
                        </button>
                        <button class="chatbot-helpful-btn" data-helpful="no" data-faq-id="${faqId}">
                            ${this.settings.strings.no}
                        </button>
                    </div>
                `;
            }

            html += `
                    </div>
                </div>
            `;

            $('#chatbot-messages').append(html);
            this.messageHistory.push({ type: 'bot', text: text, faqId: faqId });
            this.scrollToBottom();
        }

        showTyping() {
            const html = `
                <div class="chatbot-message bot">
                    <div class="chatbot-typing active">
                        <div class="chatbot-typing-dots">
                            <span></span><span></span><span></span>
                        </div>
                    </div>
                </div>
            `;
            $('#chatbot-messages').append(html);
            this.scrollToBottom();
        }

        hideTyping() {
            $('.chatbot-typing').closest('.chatbot-message').remove();
        }

        showDisambiguationOptions(message, options) {
            // Bot-Nachricht mit Auswahl-Buttons in einer Message
            let html = `
                <div class="chatbot-message bot">
                    <div class="chatbot-message-content">
                        ${message}
                    </div>
                </div>
                <div class="chatbot-message bot">
                    <div class="chatbot-message-content" style="padding: 8px;">
                        <div class="chatbot-disambiguation-options">
            `;

            options.forEach((option, index) => {
                const shortQuestion = option.question.length > 80
                    ? option.question.substring(0, 80) + '...'
                    : option.question;

                html += `
                    <button class="chatbot-disambiguation-option" data-faq-id="${option.id}">
                        ${this.escapeHtml(shortQuestion)}
                    </button>
                `;
            });

            html += `
                        </div>
                    </div>
                </div>
            `;

            $('#chatbot-messages').append(html);
            this.messageHistory.push({ type: 'bot', text: message });
            this.scrollToBottom();

            if (this.settings.historyMode === 'manual') {
                this.saveToStorage();
            }
        }

        selectDisambiguationOption(faqId) {
            // Button deaktivieren während der Anfrage
            $('.chatbot-disambiguation-option').prop('disabled', true).css('opacity', '0.5');

            // Typing-Animation zeigen
            this.showTyping();

            // AJAX-Request für spezifische FAQ
            $.post(questiData.ajaxurl, {
                action: 'questi_get_faq_by_id',
                nonce: questiData.nonce,
                faq_id: faqId,
                session_id: this.sessionId
            }, (response) => {
                this.hideTyping();

                // NUR die Buttons entfernen, nicht die Nachricht
                $('.chatbot-disambiguation-options').parent('.chatbot-message-content').parent('.chatbot-message').remove();

                if (response.success && response.data) {
                    const data = response.data;

                    if (data.answer) {
                        this.addBotMessage(data.answer, data.faq_id);
                        this.currentFaqId = data.faq_id;
                    } else {
                        this.addBotMessage('Keine Antwort verfügbar.');
                    }
                } else {
                    const errorMsg = response.data && response.data.message
                        ? response.data.message
                        : 'Fehler beim Laden der Antwort.';
                    this.addBotMessage(errorMsg);
                }

                if (this.settings.historyMode === 'manual') {
                    this.saveToStorage();
                }
            }).fail(() => {
                this.hideTyping();
                $('.chatbot-disambiguation-options').parent('.chatbot-message-content').parent('.chatbot-message').remove();
                this.addBotMessage('Verbindungsfehler. Bitte versuchen Sie es später erneut.');
            });
        }

        showContactFormButton() {
            const html = `
                <div class="chatbot-message bot">
                    <div class="chatbot-message-content">
                        <button class="chatbot-show-contact" style="background: var(--chatbot-primary-color); color: white; border: none; padding: 8px 16px; border-radius: 12px; cursor: pointer;">
                            Frage per E-Mail senden
                        </button>
                    </div>
                </div>
            `;
            $('#chatbot-messages').append(html);
            this.scrollToBottom();
        }

        showContactForm() {
            const gdprCheckbox = this.settings.gdprCheckbox ? `
                <label>
                    <input type="checkbox" id="chatbot-gdpr" required>
                    ${this.settings.gdprText}
                </label>
            ` : '';

            const html = `
                <h4 style="margin-top: 0;">Kontakt aufnehmen</h4>
                <input type="text" id="chatbot-name" placeholder="${this.settings.strings.namePlaceholder}" required>
                <input type="email" id="chatbot-email" placeholder="${this.settings.strings.emailPlaceholder}" required>
                ${gdprCheckbox}
                <button type="button" id="chatbot-contact-submit" class="chatbot-submit-button">
                    ${this.settings.strings.submit}
                </button>
                <button type="button" id="chatbot-contact-cancel" class="chatbot-cancel-button">
                    ${this.settings.strings.cancel}
                </button>
            `;

            $('#chatbot-contact-form').html(html).removeClass('hidden');
            $('#chatbot-input-area').addClass('hidden');
            this.scrollToBottom();
        }

        hideContactForm() {
            $('#chatbot-contact-form').addClass('hidden');
            $('#chatbot-input-area').removeClass('hidden');
        }

        submitContactForm() {
            const name = $('#chatbot-name').val().trim();
            const email = $('#chatbot-email').val().trim();
            const gdprChecked = this.settings.gdprCheckbox ? $('#chatbot-gdpr').is(':checked') : true;

            // Validation
            if (!name) {
                alert('Bitte geben Sie Ihren Namen ein.');
                return;
            }

            if (!email || !this.validateEmail(email)) {
                alert('Bitte geben Sie eine gültige E-Mail-Adresse ein.');
                return;
            }

            if (!gdprChecked) {
                alert('Bitte akzeptieren Sie die Datenschutzerklärung.');
                return;
            }

            // Get last user question
            const lastUserMessage = this.messageHistory.filter(m => m.type === 'user').pop();
            const question = lastUserMessage ? lastUserMessage.text : '';

            $('#chatbot-contact-submit').prop('disabled', true).text('Sende...');

            $.post(questiData.ajaxurl, {
                action: 'questi_send_inquiry',
                nonce: questiData.nonce,
                name: name,
                email: email,
                question: question,
                session_id: this.sessionId,
                faq_id: this.currentFaqId
            }, (response) => {
                if (response.success) {
                    this.hideContactForm();
                    this.addBotMessage(this.settings.thankYouMessage);
                    if (this.settings.historyMode === 'manual') {
                        this.saveToStorage();
                    }
                } else {
                    alert(response.data.message || 'Fehler beim Senden.');
                    $('#chatbot-contact-submit').prop('disabled', false).text(this.settings.strings.submit);
                }
            }).fail(() => {
                alert('Verbindungsfehler. Bitte versuchen Sie es später erneut.');
                $('#chatbot-contact-submit').prop('disabled', false).text(this.settings.strings.submit);
            });
        }

        rateAnswer(faqId, helpful) {
            $.post(questiData.ajaxurl, {
                action: 'questi_rate_answer',
                nonce: questiData.nonce,
                faq_id: faqId,
                helpful: helpful,
                session_id: this.sessionId
            });

            if (helpful === 'no') {
                setTimeout(() => {
                    this.showContactFormButton();
                }, 500);
            }
        }

        showOfflineMessage() {
            if ($('.chatbot-offline-message').length === 0) {
                $('#chatbot-widget').prepend('<div class="chatbot-offline-message">' + this.settings.strings.offline + '</div>');
            }
        }

        scrollToBottom() {
            const messages = $('#chatbot-messages');
            messages.scrollTop(messages[0].scrollHeight);
        }

        saveToStorage() {
            localStorage.setItem('chatbot_history', JSON.stringify(this.messageHistory));
        }

        loadFromStorage() {
            const stored = localStorage.getItem('chatbot_history');
            if (stored) {
                try {
                    this.messageHistory = JSON.parse(stored);
                } catch (e) {
                    // Falls JSON ungültig, leere History
                    this.messageHistory = [];
                }
            }
            // Wenn keine History vorhanden, bleibt messageHistory leer
            // Die Begrüßungsnachricht wird erst beim Öffnen des Chats angezeigt
        }

        renderHistory() {
            // DOM leeren
            $('#chatbot-messages').empty();

            // Alle Nachrichten aus History rendern
            this.messageHistory.forEach(msg => {
                if (msg.type === 'user') {
                    const html = `
                        <div class="chatbot-message user">
                            <div class="chatbot-message-content">${this.escapeHtml(msg.text)}</div>
                        </div>
                    `;
                    $('#chatbot-messages').append(html);
                } else {
                    let html = `
                        <div class="chatbot-message bot">
                            <div class="chatbot-message-content">
                                ${msg.text}
                    `;

                    if (msg.faqId) {
                        html += `
                            <div class="chatbot-helpful-buttons">
                                <small>${this.settings.strings.helpful}</small>
                                <button class="chatbot-helpful-btn" data-helpful="yes" data-faq-id="${msg.faqId}">
                                    ${this.settings.strings.yes}
                                </button>
                                <button class="chatbot-helpful-btn" data-helpful="no" data-faq-id="${msg.faqId}">
                                    ${this.settings.strings.no}
                                </button>
                            </div>
                        `;
                    }

                    html += `
                            </div>
                        </div>
                    `;
                    $('#chatbot-messages').append(html);
                }
            });
        }

        clearChat() {
            // LocalStorage löschen
            localStorage.removeItem('chatbot_history');

            // Message-History leeren
            this.messageHistory = [];

            // DOM leeren
            $('#chatbot-messages').empty();

            // Begrüßungsnachricht erneut anzeigen
            this.addBotMessage(this.settings.welcomeMessage);
            this.saveToStorage();
        }

        validateEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }

    // Initialize chatbot when DOM is ready
    $(document).ready(function() {
        new WpFaqChat();
    });

})(jQuery);

// Bonus Hunt Guesser - Public JavaScript
// Client-side validation and UX enhancements

jQuery(document).ready(function($) {
    'use strict';

    // Global variables
    var bhg_ajax_url = bhg_public_ajax.ajax_url;
    var bhg_nonce = bhg_public_ajax.nonce;

    // Initialize plugin functionality
    function initBonusHuntGuesser() {
        // Guess submission form validation
        validateGuessForm();
        
        // Leaderboard sorting functionality
        initLeaderboardSorting();
        
        // Tab switching for leaderboard views
        initLeaderboardTabs();
        
        // Login redirect handling
        handleLoginRedirects();
        
        // Affiliate status indicators
        initAffiliateIndicators();
    }

    // Validate and submit the guess form
    function validateGuessForm() {
        $('.bhg-guess-form').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var guessInput = form.find('#bhg-guess');
            var guessValue = parseFloat(guessInput.val());
            var errorContainer = form.find('.bhg-error-message');
            var isValid = true;

            // Clear previous errors
            errorContainer.html('').hide();
            guessInput.removeClass('error');

            // Validate required field
            if (!guessInput.val().trim()) {
                showError(errorContainer, bhg_public_ajax.i18n.guess_required);
                guessInput.addClass('error');
                isValid = false;
            }
            // Validate numeric value
            else if (isNaN(guessValue)) {
                showError(errorContainer, bhg_public_ajax.i18n.guess_numeric);
                guessInput.addClass('error');
                isValid = false;
            }
            // Validate range (0 - 100,000)
            else if (guessValue < 0 || guessValue > 100000) {
                showError(errorContainer, bhg_public_ajax.i18n.guess_range);
                guessInput.addClass('error');
                isValid = false;
            }

            // Prevent form submission if validation fails
            if (!isValid) {
                return false;
            }

            // Show loading indicator
            form.find('.bhg-submit-btn').prop('disabled', true).addClass('loading');

            // AJAX request to submit guess
            $.ajax({
                url: bhg_ajax_url,
                type: 'POST',
                data: {
                    action: 'submit_bhg_guess',
                    nonce: bhg_nonce,
                    guess_amount: guessValue,
                    hunt_id: form.find('[name="hunt_id"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message and reload page
                        showSuccess(errorContainer, bhg_public_ajax.i18n.guess_submitted);
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        // Show error message
                        showError(errorContainer, response.data);
                        form.find('.bhg-submit-btn').prop('disabled', false).removeClass('loading');
                    }
                },
                error: function() {
                    // Show generic error message
                    showError(errorContainer, bhg_public_ajax.i18n.ajax_error);
                    form.find('.bhg-submit-btn').prop('disabled', false).removeClass('loading');
                }
            });
        });
    }

    // Initialize leaderboard sorting
    function initLeaderboardSorting() {
        $('.bhg-leaderboard .sortable').on('click', function() {
            var table = $(this).closest('table');
            var column = $(this).data('column');
            var direction = $(this).hasClass('asc') ? 'desc' : 'asc';
            
            // Update UI
            table.find('.sortable').removeClass('asc desc');
            $(this).addClass(direction);
            
            // Sort table
            sortTable(table, column, direction);
        });
    }

    // Sort table function
    function sortTable(table, column, direction) {
        var tbody = table.find('tbody');
        var rows = tbody.find('tr').get();
        
        rows.sort(function(a, b) {
            var aVal = $(a).find('td[data-column="' + column + '"]').text().trim();
            var bVal = $(b).find('td[data-column="' + column + '"]').text().trim();
            
            // Numeric sorting for guess amounts
            if (column === 'guess') {
                aVal = parseFloat(aVal.replace(/[^\d.-]/g, ''));
                bVal = parseFloat(bVal.replace(/[^\d.-]/g, ''));
            }
            
            if (direction === 'asc') {
                return aVal > bVal ? 1 : -1;
            } else {
                return aVal < bVal ? 1 : -1;
            }
        });
        
        $.each(rows, function(index, row) {
            tbody.append(row);
        });
    }

    // Initialize leaderboard tabs
    function initLeaderboardTabs() {
        $('.bhg-tabs-nav a').on('click', function(e) {
            e.preventDefault();
            
            // Update active tab
            $('.bhg-tabs-nav a').removeClass('active');
            $(this).addClass('active');
            
            // Show corresponding tab content
            var target = $(this).data('tab');
            $('.bhg-tab-content').removeClass('active');
            $('#' + target).addClass('active');
            
            // Refresh leaderboard data via AJAX if needed
            refreshLeaderboardData(target);
        });
    }

    // Refresh leaderboard data via AJAX
    function refreshLeaderboardData(timeframe) {
        // Only load via AJAX if not already loaded
        if ($('#bhg-leaderboard-' + timeframe).hasClass('loaded')) {
            return;
        }
        
        $.ajax({
            url: bhg_ajax_url,
            type: 'POST',
            data: {
                action: 'bhg_load_leaderboard',
                timeframe: timeframe,
                nonce: bhg_nonce
            },
            beforeSend: function() {
                $('#bhg-leaderboard-' + timeframe).addClass('loading');
            },
            success: function(response) {
                if (response.success) {
                    $('#bhg-leaderboard-' + timeframe).html(response.data);
                    $('#bhg-leaderboard-' + timeframe).addClass('loaded');
                } else {
                    console.error('Error loading leaderboard:', response.data);
                }
            },
            complete: function() {
                $('#bhg-leaderboard-' + timeframe).removeClass('loading');
            }
        });
    }

    // Handle login redirects
    function handleLoginRedirects() {
        // Store current URL for redirect after login
        if ($('.bhg-guess-form').length && !bhg_public_ajax.is_logged_in) {
            sessionStorage.setItem('bhg_redirect_url', window.location.href);
        }
        
        // Check if we have a redirect URL after login
        var redirectUrl = sessionStorage.getItem('bhg_redirect_url');
        if (redirectUrl && window.location.href.indexOf('wp-login.php') !== -1) {
            $('input[name="redirect_to"]').val(redirectUrl);
            sessionStorage.removeItem('bhg_redirect_url');
        }
    }

    // Initialize affiliate status indicators
    function initAffiliateIndicators() {
        // Add tooltips to affiliate status indicators
        $('.bhg-affiliate-status').hover(function() {
            var status = $(this).hasClass('affiliate') ? 
                bhg_public_ajax.i18n.affiliate_user : 
                bhg_public_ajax.i18n.non_affiliate_user;
            
            // Show tooltip
            $(this).append('<span class="bhg-tooltip">' + status + '</span>');
        }, function() {
            // Remove tooltip
            $(this).find('.bhg-tooltip').remove();
        });
    }

    // Show error message
    function showError(container, message) {
        container.html('<div class="bhg-alert bhg-alert-error">' + message + '</div>').show();
    }

    // Show success message
    function showSuccess(container, message) {
        container.html('<div class="bhg-alert bhg-alert-success">' + message + '</div>').show();
        
        // Auto-hide success message after 5 seconds
        setTimeout(function() {
            container.fadeOut();
        }, 5000);
    }

    // Initialize the plugin
    initBonusHuntGuesser();
});
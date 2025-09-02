(function($){ $(document).ready(function(){
    // simple accordion for results blocks
    $('.bhg-hunt-result h3').on('click', function(){ $(this).nextAll('p,table').first().toggle(); $(this).closest('.bhg-hunt-result').find('table').toggle(); });
    // tabs: hide/show sections if using .bhg-tabs container
    $('.bhg-tabs .tab-buttons button').on('click', function(){ var t = $(this).data('tab'); $(this).siblings().removeClass('active'); $(this).addClass('active'); $(this).closest('.bhg-tabs').find('.tab-content').hide(); $(this).closest('.bhg-tabs').find('.tab-content[data-tab="'+t+'"]').show(); });
}); })(jQuery);
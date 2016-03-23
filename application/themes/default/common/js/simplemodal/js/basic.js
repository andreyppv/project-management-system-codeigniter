
/* NEXTLOOP: This is the div with the click button and the div with the hidden content
   More can be added as Moodal-2 etc
*/
jQuery(function ($) {
	$('#Moodal-1 .clickme').click(function (e) {
		$('#Moodal-1-Content').modal();
		return false;
	});
});



/* NEXTLOOP: Example HTML

<div id="Moodal-1">
<a href='#' class='clickme'>Click for Popup</a>
</div>
<div id="Moodal-1-Content" style="display:none;">This is hidden content
</div>

*/
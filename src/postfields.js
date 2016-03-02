function we_selectbox()
{
	var that = $(this), id = that.attr('id'), title = $('option:selected', this).val() != '' ? $('option:selected', this).text() : that.attr('title');
	that
		.hide()
		.after('<ul class="menu"><li id="' + id + '_li"><h4 class="hove"><a>' + title + '</a></h4><ul id="' + id + '_ul" style="visibility:visible;border-radius:0;opacity:1;display:none"></ul></li></ul>')
		.change(function () { $(this).next().text($('option:selected', this).text()); })
		.children('option').each(function () {
			var here = $(this), txt = here.text();
			$('#' + id + '_ul').append(txt == '-' ? '<li class="separator"><a><hr></a></li>' : '<li id="' + here.val() + '"><a>' + txt + '</a></li>');
		});
	$('#' + id + '_li').click(function () { $('#' + id + '_ul').show(); });
	$(document).mousedown(function () { $('#' + id + '_ul').hide(); });
	$('#' + id + '_ul').children('li').each(function () {
		$(this)
			.bind('mouseenter focus mouseleave blur', function () { $(this).we_ToggleClass('hove'); })
			.mousedown(function () {
				$(this).parent().children('li').removeClass('active');
				$(this).addClass('active');
				$('#' + id).val($(this).attr('id'));
			});
	});
};

$(document).ready(function(){
	$('select').selectBox();
});
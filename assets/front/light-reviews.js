var $ = jQuery.noConflict();

$('#review-btn').on('click', function(){

	const data = {
		"action": "get_review_popup"
	}
	let url = window.location.href,
		ajaxUrl = (url.includes('/ua/')) ? '/ua/wp-admin/admin-ajax.php' : '/wp-admin/admin-ajax.php';
	$.ajax({
		url: ajaxUrl,
		data: data,
		type: "POST",
		success: function (answer) {
			$('.popup-wrapper').append(answer)
			$('.popup-wrapper').fadeIn()
		},
		error: function(jqXHR, exception){
			alert('Ajax sending error. Check brauser console for detail.')
			ajaxErrorFunc(jqXHR, exception)
		}
	});
})

$('body').on('submit', '#review-form', function (e) {
	e.preventDefault();
	let form = $(this),
		url = form.attr('action'), //в url включен get параметр с именем функции, которую нужно выполнить
		data = form.serialize();
	$.ajax({
		url: url,
		data: data,
		dataType:'json',
		type: "POST",
		success: function (answer) {
			if (answer['id'] != 0) {
				$('.form-message').addClass('form-message--success')
				$('#form-message').html(answer['message'])
				setTimeout(function(){
					clearPopup()
				}, 4000)
			}
			else{
				console.error(answer['message'])
				$('.form-message').addClass('form-message--fail')
				$('#form-message').html(answer['message'])
				setTimeout(function(){
					clearPopup()
				}, 4000)
			}
		},
		error: function (jqXHR, exception) {
			$('.form-message').addClass('form-message--fail')
				$('#form-message').html('Ajax sending error. Check brauser console for detail.')
				setTimeout(function(){
					clearPopup()
				}, 4000)
			ajaxErrorFunc(jqXHR, exception)
		}
	});
})

$(document).ready(function(){

// if the user clicks on the like button ...
$('.like-btn').on('click', function(){
  var post_id = $(this).data('id');
  $clicked_btn = $(this);
  if ($clicked_btn.hasClass('fa-arrow-circle-o-up')) {
  	action = 'like';
  } else if($clicked_btn.hasClass('fa-arrow-circle-up')){
  	action = 'unlike';
  }
  $.ajax({
  	url: 'index.php',
  	type: 'post',
  	data: {
  		'action': action,
  		'post_id': post_id
  	},
  	success: function(data){
  		res = JSON.parse(data);
  		if (action == "like") {
  			$clicked_btn.removeClass('fa-arrow-circle-o-up');
  			$clicked_btn.addClass('fa-arrow-circle-up');
  		} else if(action == "unlike") {
  			$clicked_btn.removeClass('fa-arrow-circle-up');
  			$clicked_btn.addClass('fa-arrow-circle-o-up');
  		}
  		// display the number of likes and dislikes
  		$clicked_btn.siblings('span.likes').text(res.likes);
  		$clicked_btn.siblings('span.dislikes').text(res.dislikes);

  		// change button styling of the other button if user is reacting the second time to post
  		$clicked_btn.siblings('i.fa-arrow-circle-down').removeClass('fa-arrow-circle-down').addClass('fa-arrow-circle-o-down');
  	}
  });		

});

// if the user clicks on the dislike button ...
$('.dislike-btn').on('click', function(){
  var post_id = $(this).data('id');
  $clicked_btn = $(this);
  if ($clicked_btn.hasClass('fa-arrow-circle-o-down')) {
  	action = 'dislike';
  } else if($clicked_btn.hasClass('fa-arrow-circle-down')){
  	action = 'undislike';
  }
  $.ajax({
  	url: 'index.php',
  	type: 'post',
  	data: {
  		'action': action,
  		'post_id': post_id
  	},
  	success: function(data){
  		res = JSON.parse(data);
  		if (action == "dislike") {
  			$clicked_btn.removeClass('fa-arrow-circle-o-down');
  			$clicked_btn.addClass('fa-arrow-circle-down');
  		} else if(action == "undislike") {
  			$clicked_btn.removeClass('fa-arrow-circle-down');
  			$clicked_btn.addClass('fa-arrow-circle-o-down');
  		}
  		// display the number of likes and dislikes
  		$clicked_btn.siblings('span.likes').text(res.likes);
  		$clicked_btn.siblings('span.dislikes').text(res.dislikes);
  		
  		// change button styling of the other button if user is reacting the second time to post
  		$clicked_btn.siblings('i.fa-arrow-circle-up').removeClass('fa-arrow-circle-up').addClass('fa-arrow-circle-o-up');
  	}
  });	

});

});
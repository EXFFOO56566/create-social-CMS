const _concat = y => xs => xs.concat(y);

function arrayFunc(xs, yx, d) {
	if(xs.sort().toString() == yx.sort().toString()) {
		return 1;
	}

	let empt = x => y => x.length == 0 ? y.length != 0 ? y : 0 : y.length != 0 ? 1 : x;

	const a_empty = empt(xs)(yx);

	if(a_empty instanceof Array)  {
		return a_empty;
	} else if(a_empty == 0) {
		return [];
	}

	const apply = f => x => f(x);

	const flip = f => y => x => f(x)(y);

	const createMap = xs => {
			const rMap = new Map();
			xs.map(x => {
				let i = 0;
			rMap.set(i, x);
			i++;
		});
		return rMap;
	}

	const filter = f => xs => xs.filter(apply(f));

	const differencel = xs => yx => {
		const zs = createMap(yx);
		return filter(([k, x]) => zs.has(x) ? false : true) (xs);
	};

	if(d == 'left') {
		return differencel(xs)(ys);
	}

	if(d == 'join') {
		return flip(differencel)(xs)(ys);
	}

	if(d == "union") {
		return [...xs, ...yx];
	}

	const difference = yx => xs => _concat(differencel(xs)(yx))(flip(differencel)(xs)(yx));

	return difference;
}

function getLivestreamCreds() {
	let formData = new FormData();
	formData.append('val[livestream_id]', window.streamId);
	formData.append('val[last_get_time]', window.last_get_time);
	formData.append('val[offset]', window.offset);
	sendRequest(baseUrl + 'livestream/cred', formData, function(data) {
		data = JSON.parse(data);
		window.last_get_time = data.last_get_time;
		//var data = Object.values(data);
		refreshData(data);
		fetchCommentData();
	});
}

function submitLivestreamCreds(action) {
	if(action == "like") {
		let like_counter = document.getElementById('like');
		like_counter.innerHTML = window.like_count > 1 ? `<a href="#" onClick="submitLivestreamCreds('like')"><i class="ion-thumbsup"></i></a>You and ${window.like_count} others like this` : `<a href="#"><i class="ion-thumbsup"></i></a>You like this`;
	}

	let formData = new FormData();
	formData.append('val[livestreamId]', window.streamId);
	formData.append('val[action]', action);

	sendRequest(baseUrl + 'livestream/submitcred', formData, function(data) {

	});
}

function submitCommentInfo(action, comment_id) {
	const comment_string = `
		<li>
			<div><div class="upper-div" style="width:20%; max-width:32px; margin-right:3px"><img src="" height="30" width="30"/></div>
				<div class="upper-div" style="width:78%; min-width:250px"><a href="">You:</a> ${document.getElementById('comment_text').value}</div></div>
			<div class="clear"></div>
			<div class="social"><span class="like"><a href="#"  onClick="submitCommentInfo('comment_like', 1)"><i class="ion-thumbsup"></i></a></span><span id="comment_like_count" class="like-count">0 likes</span></div>
		</li>`;
	let comments = document.getElementById('comments');
	comments.innerHTML = comments.innerHTML + comment_string;

	let cm_text = document.getElementById('comment_text').value;
	document.getElementById('comment_text').value = "";

	let formData = new FormData();
	formData.append('val[livestreamId]', window.streamId);
	formData.append('val[action]', action);
	formData.append('val[text]', cm_text);
	formData.append('val[comment_id]', comment_id);

	sendRequest(baseUrl + 'livestream/submitcred', formData, function(data) {
		let comment = JSON.parse(data);
	});
}

function registerAjax(commentId, gId, likes, comments) {
	commentMap.push([commentId, gId, likes, comments]);
}

function fetchCommentData() {
	comment_ids = "";
	commentMap.map(([i, j, k, l]) => comment_ids += i + "_");

	$.ajax({
		url: baseUrl + 'livestream/commentdata?id=' + comment_ids + '&csrf_token=' + requestToken,
		success: function(data) {
			data = JSON.parse(data);
			if(data.length == 0) return false;
			let counter = 0;
			commentMap.map(([u, v, w, y]) => {
				if(y != data[`a${u}`].replies) {
					let container = $(".comment-replies-" + v);
					let repliesLink = $(".comment-replies-" + v + " .load-replies-link");
					//container.find('.comment-lists').html(data).css("padding", "10px 0");
					if(repliesLink.length > 0) {
						repliesLink.remove();
					}
					//$('.comment-replies-914578769e920146ec3240e00432f0bf').find('.comment-lists').html(`<a class="load-replies-link" onclick="return show_comment_replies('101', 'ew9939w0oe;sd')" href=""><i class="ion-reply"></i> View 78 Reply <img src="${window.loaderImg}" style="display:none"></a>`)
					let replies = data[`a${u}`].replies;
					if(replies > 0) container.find('.comment-lists').html(`<a class="load-replies-link" onclick="return show_comment_replies('${u}', '${v}')" href=""><i class="ion-reply"></i> View ${replies} Reply <img src="${window.loaderImg}" style="display:none"></a>`).css("padding", "10px 0");
					commentMap[counter][3] = data['a' + u].replies;
				}

				if(w != data[`a${u}`].likes) {
					var count = $(".like-count-comment-" + u);
					count.html(data[`a${u}`].likes);
					commentMap[counter][2] = data[`a${u}`].likes;
				}
				counter++;
			});
			reloadInits();
		}
	});
	return false;
}

function reloadIt() {

}

function refreshData(data) {
	if(window.comment_count != data.comment_count) {
		$(".comment-count-livestream-" + window.streamId).html(data.comment_count);
		window.comment_count = data.comment_count;
	}

	if(window.participants_count != data.participants_count) {
		let participants_counter = document.getElementById('viewers');
		participants_counter.innerHTML = `Viewers: ${data.participants_count}`;
		window.participants_count = data.participants_count;
	}

	if(window.like_count != data.like_count) {
		$(".like-count-livestream-" + streamId).html(data.like_count);
		window.like_count = data.like_count;
	}

	if(data.has_ended) {
		qNotify('Livestream Ended', "Livestream has ended", 'danger');
		window.watching = false;
		window.unPlug = true;
	}

	if(data.unseen_comments) {
		var c = $('.comment-lists-livestream-' + streamId);
		c.append(data.unseen_comments);
		reloadInits();
	}

	if(data._join) {
		for(var i = 0; i < data._join.length; i++) {
			if(i > 0) {
				setTimeout(function() {
					qNotify("User Join", data._join[i].message);
				}, 6000);
			} else {
				qNotify("User Join", data._join[i].message);
			}
		}
	}

	if(data._left) {
		for(var i = 0; i < data._left.length; i++) {
			if(i > 0) {
				setTimeout(function() {
					qNotify("User Join", data._left[i].message, 'danger');
				}, 6000);
			} else {
				qNotify("User Join", data._left[i].message, 'danger');
			}
		}
	}
}

function showParticipant(data) {

}

var t = 0;

function moveit() {

	var r = 100;         // radius
	var xcenter = 100;   // center X position
	var ycenter = 100;   // center Y position

	let elem = $('.like-button-livestream-' + streamId + ' .ion-thumbsup');

	while(t <= 360) {
		var newLeft = Math.floor(xcenter + (r * Math.cos(t)));
		var newTop = Math.floor(ycenter + (r * Math.sin(t)));

		elem.animate({
			top: newTop,
			left: newLeft,
		}, 1, function() {
			//moveit();
		});
		t += 0.05;
	}

	elem.remove();

	$('.like-button-livestream-' + streamId).html('<i class="ion-thumbsup"></i>');
}

function qNotify(title, content, mood) {
	mood = mood || 'success';
	let html_content = `
			<button type="button" class="close" data-dismiss="alert">x</button>
			<strong>${title}! </strong><br/>
			${content}`;

	//$("#livestream-overlay").remove("#q-notifier");

	$("#q-notifier").attr('class', 'alert alert-' + mood);
	$("#q-notifier").html(html_content);
	$("#q-notifier").fadeIn(500).delay(5000).fadeOut(500);
}

function change_livestream_source(t) {
	var o = $(t);
	var v = o.val();

	$(".livestream-source-selector .source").hide();
	$(".livestream-source-selector  ." + v).fadeIn();

	if(v == 'upload') {
		$('.livestream-details-container').show();
	} else {
		$('.livestream-details-container').hide();
	}

	return true;
}

function livestream_form_list_url() {
	var form = $('#livestream-list-search');
	var v = form.find('input[type=text]').val();
	var cat = $("#livestream-category-list").val();

	var type = form.find('.livestream-type-input').val();
	var filter = $("#livestream-filter-select").val();
	var url = baseUrl + 'livestreams?term=' + v + "&category=" + cat + "&type=" + type + '&filter=' + filter;

	return url;
}

function livestream_submit_search(t) {
	var url = livestream_form_list_url();

	loadPage(url);
	return false;
}

function livestream_list_change_category(t) {
	var url = livestream_form_list_url();
	loadPage(url);
}

function sendRequest(url, data, callback) {
	var request = new XMLHttpRequest();

	request.onreadystatechange = function() {
		if(request.readyState == 4 && request.status == 200) {
			callback(request.responseText);
		}
	};

	request.open('POST', url);
	request.send(data);
}

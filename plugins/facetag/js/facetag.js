if (typeof faceTag === 'undefined') {
    window.faceTag = {
		searching: false,

		init: function() {
			var img = document.getElementById('ft-img');
			img.onload = function() {
				faceTag.detect(this.getAttribute('data-id'), this.src);
			};
			Hook.register('photo.viewer.loaded', faceTag.imageLoaded);
		},

		imageLoaded: function(result, id, link) {
			var img = document.getElementById('ft-img');
			img.src = link;
			img.setAttribute('data-id', id);
		},

		setCoordinate: function(user) {
			var coordID = $(user).data('coord-id');
			var userID = $(user).data('id');
			var userName = $(user).data('name');
			var rect = $('#ft-rect-' + coordID);
			var tag = $('#ft-tag-' + coordID);
			var photoID = rect.data('p');
			var img = document.getElementById('photo-viewer-image');
			var ftImg = document.getElementById('ft-img');
			var x = parseInt(rect.data('x'));
			rect.removeClass('active');
			tag.find('.name').html(userName);
			tag.addClass('active');
			$.get(baseUrl + 'facetag/ajax/set_coordinate?coord_id=' + coordID + '&photo_id=' + photoID + '&user_id=' + userID + '&csrf_token=' + requestToken);
		},

		removeCoordinate: function(coordID) {
			var rect = $('#ft-rect-' + coordID);
			var tag = $('#ft-tag-' + coordID);
			tag.removeClass('active');
			rect.addClass('active');
			$.get(baseUrl + 'facetag/ajax/remove_coordinate?coord_id=' + coordID + '&csrf_token=' + requestToken);
		},

		searchPeople: function(coordID) {
			if(faceTag.searching) {
				return false;
			}
			faceTag.searching = true;
			var query = $('#ft-search-' + coordID).val();
			var container = $('#ft-search-result-' + coordID)
			if(query.length >= 3) {
				container.addClass('active');
				$.ajax({
					url: baseUrl + 'facetag/ajax/search_people?q=' + query + '&coord_id=' + coordID + '&csrf_token=' + requestToken,
					success: function(data) {
						faceTag.searching = false;
						container.html(data).find('.user').click(function() {
							faceTag.setCoordinate(this);
						});
					}
				});
			} else {
				faceTag.searching = false;
				container.removeClass('active');
			}
		},

		detect: function(id, link) {
			var img = document.getElementById('ft-img');
			var tracker = new tracking.ObjectTracker('face');
			var plotRectangle = function(x, y, w, h, p, f) {
				var coordID = p + '-' + x + '-' + y;
				var container = document.getElementById('photo-viewer-wrapper');
				var rect = document.createElement('div');
				var tag = document.createElement('div');
				var name = document.createElement('div');
				var remove = document.createElement('div');
				var arrow = document.createElement('div');
				var search = document.createElement('input');
				var searchResult = document.createElement('div');
				
				container.style.width =  document.getElementById('photo-viewer-image').offsetWidth + 'px';
				container.style.height = document.getElementById('photo-viewer-image').offsetHeight + 'px';

				rect.id = 'ft-rect-' + coordID;
				rect.classList.add('rect');
				if(!(coordID in f['tags']) && f['can_tag_photo']) {
					rect.classList.add('active');
				}
				rect.setAttribute('data-x', x);
				rect.setAttribute('data-y', y);
				rect.setAttribute('data-w', w);
				rect.setAttribute('data-h', h);
				rect.setAttribute('data-p', p);
				rect.style.width = (w / img.offsetWidth * 100) + '%';
				rect.style.height = (h / img.offsetHeight * 100) + '%';
				rect.style.left = ((img.offsetLeft + x) / img.offsetWidth * 100) + '%';
				rect.style.top = ((img.offsetTop + y) / img.offsetHeight * 100) + '%';

				arrow.id = 'ft-arrow-' + coordID;
				arrow.classList.add('arrow');
				arrow.style.left = ((img.offsetLeft + x + (w / 2) - 10) / img.offsetWidth * 100) + '%';
				arrow.style.top = ((img.offsetTop + y + h) / img.offsetHeight * 100) + '%';

				search.id = 'ft-search-' + coordID;
				search.classList.add('search');
				search.name = 'name';
				search.type = 'text';
				search.value = '';
				search.setAttribute('autocomplete', 'off');
				search.setAttribute('placeholder', f['phrases']['tag-somebody']);
				search.style.left = ((img.offsetLeft + x + (w / 2) - (180 / 2)) / img.offsetWidth * 100) + '%';
				search.style.top = ((img.offsetTop + y + h + 10) / img.offsetHeight * 100) + '%';

				searchResult.id = 'ft-search-result-' + coordID;
				searchResult.classList.add('search-result');
				searchResult.style.left = ((img.offsetLeft + x + (w / 2) - (180 / 2)) / img.offsetWidth * 100) + '%';
				searchResult.style.top = ((img.offsetTop + y + h + 10 + 30 + 4) / img.offsetHeight * 100) + '%';

				tag.id = 'ft-tag-' + coordID;
				tag.classList.add('tag');
				if(coordID in f['tags']) {
					tag.classList.add('active');
				}
				tag.style.left = ((img.offsetLeft + x) / img.offsetWidth * 100) + '%';
				tag.style.top = ((img.offsetTop + y + (h / 2) - (18 / 2)) / img.offsetHeight * 100) + '%';

				name.id = 'ft-name-' + coordID;
				name.classList.add('name');
				if(coordID in f['tags']) {
					var profileLink = "<a href='"+baseUrl+f['tags'][coordID]['username']+"'>"+ f['tags'][coordID]['name'] +"</a>";
					name.innerHTML = profileLink;
				}

				remove.id = 'ft-remove-' + coordID;
				remove.classList.add('remove');
				remove.classList.add('ion-close');

				tag.appendChild(name);
				if(f['can_tag_photo']) {
					tag.appendChild(remove);
				}
				container.appendChild(rect);
				container.appendChild(arrow);
				container.appendChild(search);
				container.appendChild(searchResult);
				container.appendChild(tag);

				rect.onclick = function() {
					search.select();
				};

				rect.onmouseover = function() {
					if(/active/.test(this.className)) {
						arrow.classList.add('active');
						search.classList.add('active');
					}
				};

				rect.onmouseout = function() {
					if(document.activeElement != search) {
						arrow.classList.remove('active');
						search.classList.remove('active');
					}
				};

				remove.onclick = function() {
					faceTag.removeCoordinate(coordID);
				};

				search.onfocus = function() {
					faceTag.searchPeople(coordID);
				};
				search.onkeyup = function() {
					faceTag.searchPeople(coordID);
				};
				search.onchange = function() {
					faceTag.searchPeople(coordID);
				};
				search.onblur = function() {
					arrow.classList.remove('active');
					search.classList.remove('active');
					searchResult.classList.remove('active');
				};
			};
			try  {
				var xhr = new ActiveXObject("Msxml2.XMLHTTP");
			} catch (e) {
				try {
					var xhr = new ActiveXObject("Microsoft.XMLHTTP");
				} catch (e) {
					var xhr = false;
				}
			}
			if (!xhr && typeof XMLHttpRequest != 'undefined') {
				var xhr = new XMLHttpRequest();
			}
			xhr.open('GET', baseUrl + 'facetag/ajax/get_tags?photo_id=' + id + '&csrf_token=' + requestToken);
			xhr.onreadystatechange = function() {
				if (xhr.readyState == 4 && xhr.status == 200) {
					var faceTags = JSON.parse(xhr.responseText);
					tracking.track(img, tracker);
					tracker.on('track', function(event) {
						event.data.forEach(function(rect) {
							plotRectangle(rect.x, rect.y, rect.width, rect.height, id, faceTags);
						});
					});
				}
			}
			xhr.send(null);
		}
	}
}

$(function() {
	faceTag.init();
});
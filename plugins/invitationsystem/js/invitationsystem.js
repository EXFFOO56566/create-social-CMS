if(typeof invitationSystem === 'undefined') {
	window.invitationSystem = {
		showInvitationCodeModal: function(button) {
			var invitationCode = $(button).data('invitation-code');
			var invitationSignupLink = $(button).data('invitation-signup-link');
			var invitationCodeExpiryTime = $(button).data('invitation-code-expiry-time');
			var modal = $('#invitationsystem-invitation-code-modal');
			modal.find('.invitationsystem-invitation-code').html(invitationCode);
			modal.find('.invitationsystem-invitation-signup-link').html(invitationSignupLink);
			modal.find('.invitationsystem-invitation-code-expiry-time').html(invitationCodeExpiryTime);
			modal.modal('show');
		},

		paginateInviteds: function(user_id, page) {
			var modal = $('#invitationsystem-admin-invitation-modal');
			modal.modal('show');
			var loading = modal.find('.foreground');
			loading.fadeIn();
			var indicator = modal.find('.indicator');
			indicator.fadeIn();
			var friends = modal.find('#invitationsystem-admin-invitateds-list');
			friends.html('');
			$.ajax({
				url: baseUrl + 'admincp/invitationsystem/ajax?action=admin_inviteds&user_id=' + user_id + '&page=' + page + '&csrf_token=' + requestToken,
				success: function(data) {
					indicator.hide();
					loading.hide();
					$(modal).html(data);
				}
			});
			return false;
		},

		attachEvents: function() {
			$('#invitationsystem-regenerate-invitation-code-button').click(function(e) {
				e.preventDefault();
				var modal = $('#invitationsystem-invitation-code-modal');
				modal.modal('show');
				var loading = modal.find('.foreground');
				loading.fadeIn();
				var indicator = modal.find('.indicator');
				indicator.fadeIn();
				$.getJSON(baseUrl + 'invitationsystem/ajax?action=regenerate_invitation_code&csrf_token=' + requestToken, function(data) {
					indicator.hide();
					var messageParent = modal.find('.message');
					var message = data.status == true ? modal.find('.alert-success') : modal.find('.alert-danger');
					messageParent.fadeIn();
					message.fadeIn().css('display', 'inline-block');
					if(data.status == true) {
						var invitationCode = data.code;
						var invitationSignupLink = baseUrl + 'signup?invitation_code=' + invitationCode;
						var invitationCodeExpiryTime = data.expiry_time;
						modal.find('.invitationsystem-invitation-code').html(invitationCode);
						modal.find('.invitationsystem-invitation-signup-link').html(invitationSignupLink);
						modal.find('.invitationsystem-invitation-code-expiry-time').html(invitationCodeExpiryTime);
						$('.invitationsystem-invitation-code-modal-button').data('invitation-code', invitationCode);
						$('.invitationsystem-invitation-code-modal-button').data('invitation-signup-link', invitationSignupLink);
						$('.invitationsystem-invitation-code-modal-button').data('invitation-code-expiry-time', invitationCodeExpiryTime);
					}
					setTimeout(function() {
						message.hide();
						messageParent.hide();
						loading.hide();
					}, 2000);
				});
				return false;
			});
			$('a[href$="signup"]').click(function(e) {
				e.preventDefault();
				$('#invitationsystem-modal').modal();
			});
			$('#invitationsystem-invitation-submit-button').click(function(e) {
				e.preventDefault();
				var modal = $('#invitationsystem-modal');
				modal.modal('show');
				var loading = modal.find('.foreground');
				loading.fadeIn();
				var indicator = modal.find('.indicator');
				indicator.fadeIn();
				var invitationCode = $('#invitationsystem-invitation-code').val();
				$.getJSON(baseUrl + 'invitationsystem/ajax?action=validate_invitation_code&invitation_code=' + invitationCode + '&csrf_token=' + requestToken, function(data) {
					indicator.hide();
					var messageParent = modal.find('.message');
					var message = data.status == true ? modal.find('.alert-success') : modal.find('.alert-danger');
					messageParent.fadeIn();
					message.fadeIn().css('display', 'inline-block');
					if(data.status == true) {
                        //loadPage(baseUrl + 'signup?invitation_code=' + invitationCode);
                        window.location.href = baseUrl + 'signup?invitation_code=' + invitationCode;
                    }
					setTimeout(function() {
						message.hide();
						messageParent.hide();
						loading.hide();
						if(data.status == true) {
							modal.modal('hide');
						}
					}, 2000);
				});
				return false;
			});
			$('.invitationsystem-admin-see-invitation-button').click(function(e) {
				e.preventDefault();
				var url = $(this).attr('href') + '&csrf_token=' + requestToken;
				if(url.indexOf('#') == 0) {
					$(url).modal('open');
				} else {
					$('#invitationsystem-admin-invitation-modal').html('').modal();
					$.get(url, function(data) {
						$('#invitationsystem-admin-invitation-modal').html(data);
					}).success(function() {
						$('input:text:visible:first').focus();
					});
				}
			});
			$('.invitationsystem-invitation-code-modal-button').click(function(e) {
				e.preventDefault();
				invitationSystem.showInvitationCodeModal(this);
			});
			try {
				addPageHook('invitationSystem.pageLoadHook');
			} catch(e) {

			}
		},

		pageLoadHook: function() {
			$('.invitationsystem-invitation-code-modal-button').click(function(e) {
				e.preventDefault();
				invitationSystem.showInvitationCodeModal(this);
			});
		}
	}
}
try {
	invitationSystem.attachEvents();
} catch (e) {

}
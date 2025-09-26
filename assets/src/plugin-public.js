import './styles/public.scss';

/**
 * @function onload The window.onload function is called when the page is loaded
 */
window.addEventListener('load', () => {
	// Write in console log the PHP value passed in enqueue_js_vars in frontend/class-enqueue.php
	( () => {

			// Use event delegation for all form submissions
			document.addEventListener('submit', async (e) => {

				// Check if the submitted element is a CFA form
				if (!e.target.matches('.facioj-form')) return;

				e.preventDefault();
				const form = e.target;
				const formContainer = form.closest('.facioj-contact-form');


				const messageDiv = formContainer.querySelector('.facioj-message');
				const restUrl = window.facioj_form_vars.rest_url;

				// Show loading state
				messageDiv.innerHTML = `<div class="facioj-loading">${window.facioj_form_vars.i18n.sending}</div>`;
				messageDiv.classList.remove('error', 'success');

				try {
					const formData = new FormData(form);
					const response = await fetch(restUrl, {
						method: 'POST',
						headers: {
							'X-WP-Nonce': window.facioj_form_vars.nonce // wp_rest nonce
						},
						body: formData
					});

					const result = await response.json();

					if (response.ok) {
						messageDiv.innerHTML = `<div class="facioj-success">${result.message}</div>`;
						messageDiv.classList.add('success');
						form.reset();
					} else {
						throw new Error(result.message || window.facioj_form_vars.i18n.submission_failed);
					}
				} catch (error) {
					messageDiv.innerHTML = `<div class="facioj-error">${error.message}</div>`;
					messageDiv.classList.add('error');
				}
			});

	} )();
	// Place your public-facing magic js 🪄 here
});

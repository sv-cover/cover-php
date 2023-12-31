<?php

class DevicesessionsView extends CRUDView
{
	public function render_index($iters) {
		$sessions_view = View::byName('sessions');
		return $this->render('index.twig', compact('iters', 'sessions_view'));
	}

	public function render_create_session() {
		return $this->render('create.twig');
	}

	public function is_device_session() {
		return !get_auth()->logged_in() && is_a(get_identity(), 'DeviceIdentityProvider');
	}
}

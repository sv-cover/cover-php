<?php

class DevicesessionsView extends View
{
    public function render_index($iters) {
        $sessions_view = View::byName('sessions');
        return $this->render('index.twig', compact('iters', 'sessions_view'));
    }

    public function render_create() {
        return $this->render('create.twig');
    }
}

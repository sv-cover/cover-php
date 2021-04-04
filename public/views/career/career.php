<?php

class CareerView extends View
{
    public function render_index($partners)
    {
        return $this->render('index.twig', compact('partners'));
    }
}

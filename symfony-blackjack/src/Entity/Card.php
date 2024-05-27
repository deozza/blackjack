<?php

namespace App\Entity;

class Card {
    private string $suit;
    private string $value;

    public function __construct(string $suit, string $value) {
        $this->suit = $suit;
        $this->value = $value;
    }

    public function getSuit(): string {
        return $this->suit;
    }

    public function getValue(): string {
        return $this->value;
    }
}
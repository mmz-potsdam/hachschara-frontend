<?php

namespace App\Entity;

interface JsonLdSerializable
{
    public function jsonLdSerialize($locale);
}

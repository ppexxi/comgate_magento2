<?php

// Plugin initialization (registration) code
\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'ComGate_ComGateGateway',
    __DIR__
);

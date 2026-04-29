<?php

namespace Illuminate\Bus;

class ResumeState
{
    public array $orderedSteps = [];

    public int $stepIndex = 0;

    public array $stateData = [];
}

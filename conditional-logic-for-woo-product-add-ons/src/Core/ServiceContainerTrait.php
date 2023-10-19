<?php namespace MeowCrew\AddonsConditions\Core;

trait ServiceContainerTrait {
	public function getContainer() {
		return ServiceContainer::getInstance();
	}
}

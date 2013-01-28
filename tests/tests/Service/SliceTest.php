<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class SliceTest extends sly_BaseTest {

	protected function getDataSetName() {
		return 'pristine-sally';
	}

	public function getService() {
		return sly_Core::getContainer()->getSliceService();
	}

	public function testCreate() {
		$service = $this->getService();
		$slice   = $service->create(array('module' => 'test', 'serialized_values' => array('test' => 'not empty')));

		$this->assertInstanceOf('sly_Model_Slice', $slice);
		$this->assertGreaterThan(0, $slice->getId());

		$slice = new sly_Model_Slice();
		$slice->setModule('test');
		$slice->setValues(array('test' => 'not empty'));

		$slice = $service->save($slice);

		$this->assertInstanceOf('sly_Model_Slice', $slice);
		$this->assertGreaterThan(1, $slice->getId());
	}

	public function testCopy() {
		$service = $this->getService();
		$slice   = new sly_Model_Slice();
		$slice->setModule('test');
		$slice->setValues(array('test' => 'not empty'));

		$slice  = $service->save($slice);
		$slice2 = $service->copy($slice);

		$this->assertEquals($slice2->getValues(), $slice->getValues());
		$this->assertEquals($slice2->getModule(), $slice->getModule());
		$this->assertNotEquals($slice2->getId(), $slice->getId());
	}

	public function testDelete() {
		$service = $this->getService();
		$slice   = new sly_Model_Slice();
		$slice->setModule('test');
		$slice->setValues(array('test' => 'not empty'));

		$slice  = $service->save($slice);

		$id = $slice->getId();

		$service->deleteBySlice($slice);

		$this->assertNull($service->findById($id));
	}

}

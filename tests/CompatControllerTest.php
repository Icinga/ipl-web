<?php

namespace ipl\Tests\Web;

use Icinga\Web\UrlParams;
use InvalidArgumentException;
use ipl\Orm\Model;
use ipl\Orm\Query;
use ipl\Web\Compat\CompatController;
use Zend_Controller_Response_Abstract;
use Zend_Controller_Request_Abstract;

/**
 * @runTestsInSeparateProcesses
 */
class CompatControllerTest extends TestCase
{
    protected $controller;

    protected $query;

    public function setUp(): void
    {
        class_alias('ipl\Tests\Web\SortControl', 'ipl\Web\Control\SortControl');

        $this->controller = new class extends CompatController {

            protected $params;

            public function __construct(
                Zend_Controller_Request_Abstract $request = null,
                Zend_Controller_Response_Abstract $response = null,
                array $invokeArgs = []
            ) {
                $this->params = new UrlParams();
            }
        };

        $self = (new self());
        $model = $self->createMock(Model::class);
        $model->method('getDefaultSort')->willReturn(['age']);

        $this->query = $self->createMock(Query::class);
        $this->query->method('getModel')->willReturn($model);
    }

    public function testCreateSortControlUsesDefaultSortFromModel(): void
    {
        $sortControl = $this->controller->createSortControl(
            $this->query,
            [
                'name' => 'Name',
                'age' => 'Age',
                'city' => 'City'
            ]
        );

        $this->assertSame('age', $sortControl->getDefault());
    }

    public function testCreateSortControlUsesDefaultSortFromModelWhichIsNotPresentInProvidedColumns(): void
    {
        $sortControl = $this->controller->createSortControl(
           $this->query,
            [
                'name' => 'Name',
                'surname' => 'Surname',
                'city' => 'City'
            ]
        );

        $this->assertSame('age', $sortControl->getDefault());
    }

    public function testCreateSortControlUsesProvidedThirdParamAsString(): void
    {
        $sortControl = $this->controller->createSortControl(
            $this->query,
            [
                'name' => 'Name',
                'age' => 'age',
                'city' => 'City'
            ],
            'city'
        );

        $this->assertSame('city', $sortControl->getDefault());
    }

    public function testCreateSortControlUsesProvidedThirdParamAsArray(): void
    {
        $sortControl = $this->controller->createSortControl(
            $this->query,
            [
                'name' => 'Name',
                'age' => 'age',
                'city' => 'City'
            ],
            ['city']
        );

        $this->assertSame('city', $sortControl->getDefault());
    }

    public function testCreateSortControlThrowsExceptionWhenProvidedThirdParamIsNotPresentInProvidedColumns(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid default sort "car" given');

       $this->controller->createSortControl(
            $this->query,
            [
                'name' => 'Name',
                'age' => 'age',
                'city' => 'City'
            ],
            ['car']
        );
    }
}

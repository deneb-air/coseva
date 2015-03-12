<?php

use \Coseva\CSV;

class CSVTest extends \Codeception\TestCase\Test
{
    use \Codeception\Specify;
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected $filename;
    protected $csv;

    protected function _before()
    {
        $this->filename = codecept_data_dir().'books.csv';

        $this->csv = new CSV($this->filename);
        $this->csv->setDelimiter(';');
    }

    protected function _after()
    {
    }

    /**
     * This function allows check a few exceptions in one test.
     *
     * @param callable $call Test function.
     * @param array $params  Params for test function.
     * @param string $type   Type of the expected exception.
     * @param string $message
     */
    private function _expectedException(callable $call, array $params = [], $type = '')
    {
        $message = 'Failed asserting that exception';
        try {
            call_user_func_array($call, $params);

            if (!empty($type)) $message .= ' of type '.var_export($type, true);
            $message .= ' is thrown.';

        } catch (Exception $e) {
            if (empty($type) || ($e instanceof $type)) {
                $this->assertTrue(true);
                return;
            }
            $message .= ' of type '.var_export(get_class($e), true);
            $message .= ' matches expected exception '.var_export($type, true).'.';
            $message .= ' Message was: "'.$e->getMessage().'".';
        }

        $this->fail($message);
    }

    /**************************************************************************/
    public function testSimple()
    {
        $this->assertCount(7, $this->csv);
    }

    /**************************************************************************/
    public function testArrayAccess()
    {
        $this->csv->parse();

        $this->specify('First row, first col', function(){
            $this->assertEquals('id', $this->csv[0][0]);
        });

        $this->specify('First row, non-existent col', function(){
            $this->_expectedException(function(){$this->csv[0][50];});
        });

        $this->specify('Non-existent row', function(){
            $this->_expectedException(
                function(){$this->csv[50];}, [],
                'InvalidArgumentException'
            );
        });

        $this->specify('Valid key', function(){
            $this->assertArrayHasKey(0, $this->csv);
        });

        $this->specify('Invalid key', function(){
            $this->assertArrayNotHasKey(50, $this->csv);
        });
    }

    /**************************************************************************/
    public function testArrayAutoParse()
    {
        $this->specify('Automatic parse csv file', function(){
            $this->assertEquals('id', $this->csv[0][0]);
        });
    }

    /**************************************************************************/
    public function testArrayModification()
    {
        $this->specify('Direct modification is disable', function(){
            $this->_expectedException(
                function(){$this->csv[0] = [];}, [], 'LogicException'
            );
        });

        $this->specify('Inirect modification is disable', function(){
            $this->_expectedException(
                function(){$this->csv[0][0] = 0;}
            );
        });

        $this->specify('Unset is disable', function(){
            $this->_expectedException(
                function(){unset($this->csv[0]);}, [], 'LogicException'
            );
        });
    }

    /**************************************************************************/
    public function testHeader()
    {
        $this->csv->setHeader(true);
        $this->csv->parse();

        $this->specify('Count of rows', function(){
            $this->assertCount(6, $this->csv);
        });

        $this->specify('Non-existent row "0"', function(){
            $this->_expectedException(
                function(){$this->csv[0];}, [],
                'InvalidArgumentException'
            );
        });

        $this->specify('Existed field "title"', function(){
            $this->assertArrayHasKey('title', $this->csv[1]);
        });

        $this->specify('Existed offset "3"', function(){
            $this->assertArrayHasKey(3, $this->csv[1]);
        });

        $this->specify('Existed field "description"', function(){
            $this->assertArrayHasKey("description", $this->csv[1]);
        });

        $this->specify('Non-existed offset "1"', function(){
            $this->assertArrayNotHasKey(1, $this->csv[1]);
        });

        $this->specify('Check author', function(){
            $this->assertEquals('Scott Meyers', $this->csv[2]['author']);
        });
    }

    /**************************************************************************/
    public function testField()
    {
        $this->csv->setFields(['a','b', '', 'c', 'd', 'e']);
        $this->csv->parse(1);

        $row = $this->csv[1];
        $this->specify('Existed fields 01', function() use($row){
            $this->assertArrayHasKey('a', $row);
            $this->assertArrayHasKey(2,   $row);
            $this->assertArrayHasKey('d', $row);
        });

        $this->specify('Not-Existed fields 01', function() use($row){
            $this->assertArrayNotHasKey('A', $row);
            $this->assertArrayNotHasKey(1,   $row);
            $this->assertArrayNotHasKey('e', $row);
        });

        $this->csv->setFields(['a' =>'alpha', 'b' => 'beta', 2 => 'gamma', 'c' => '', 'd' => 'delta', 'e' => 'epsilon']);
        $row = $this->csv[1];
        $this->specify('Existed fields 02', function() use($row){
            $this->assertArrayHasKey('alpha', $row);
            $this->assertArrayHasKey('c',     $row);
            $this->assertArrayHasKey('delta', $row);
        });

        $this->specify('Not-Existed fields 02', function() use($row){
            $this->assertArrayNotHasKey(1,         $row);
            $this->assertArrayNotHasKey('epsilon', $row);
        });
    }
}

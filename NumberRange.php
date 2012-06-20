<?php
/**
 * PHP version of the PERL module Number::Range
 *
 * @author  Mark Mitchell (mmitchell@riccagroup.com)
 * @see http://search.cpan.org/dist/Number-Range/
 *
 * Number::Range will take a description of a range, and then allow you to
 * test on if a number falls within the range. You can also add and delete from
 * the range.
 *
 * @example  RANGE FORMAT
 * 
 * As a single string: '1..2'
 * As multiple strings: '"1..2","3..4"'
 * As a single srting of ranges comma seperated: '1..2,3..4,-10..-5'
 * 
 * The format used for range is pretty straight forward. To separate sections of
 * ranges it uses a , or whitespace. To create the range, it uses .. to do this,
 * much like Perl's own binary .. range operator in list context.
 * 
 */ 
class NumberRange {

    /**
     * @var string
     * Based off of PERL module version
     */
    public $VERSION = '0.09';
    
    /**
     * @var array
     * Range hash computed from the range input
     */
    private $_rangehash = array();
    
    /**
     * @var boolean
     * True if the range is a negated range
     */
    public $negatedRange = false;

    /**
     * Class constuctor
     *
     * @param   $range  string      Range string ie(1..2) || "1..2","3..4" || '1..2,3..4,-10..-5'
     * @return  void
     */
    public function __construct($range) {
      # Max size of range before its stored as a pointer instead of hashed
      $this->{max_hash_size} = 1000;
      $this->initialize("add", array($range));
    }

    /**
     * Set the maximum hash size that will be stored.
     * All other ranges are stored as "hash pointers"
     *
     * @param integer   $size   max_hash_size value
     * @return  mixed   false if $size is non numberic, otherwise the new max_hash_size value
     */
    public function set_max_hash_size($size) {
      if(!is_numeric($size)) { return false; }
      $this->{max_hash_size} = $size;
      return $this->{max_hash_size};
    }
    
    /**
     * Range initialize
     *
     * @param   $type       Range type ('add' || 'del')
     * @param   $range      Range string
     * @return  void
     */
    public function initialize($type, $range) {
        if (is_string($range[0])) {
          $this->negatedRange = preg_match('/^[\!,N,n]/', $range[0]);
        }
        // Clean up number ranges that use a dash range seperator
        foreach($range as $i=>$v) {
          if(!is_string($v)) { continue; } // Next if not not string
          $range[$i] = $this->cleanDashedRange($v);
        }        
        $rangesep = '/(?-xism:(?:\.\.))/';
        $sectsep = '/(?-xism:(?:\s|,))/';
        $validate = '/(?x-ism:(?:[^0-9,. -]|(?-xism:(?:\.\.))(?-xism:(?:\s|,))|(?-xism:(?:\s|,))(?-xism:(?:\.\.))|\d-\d|^(?-xism:(?:\s|,))|^(?-xism:(?:\.\.))|(?-xism:(?:\s|,))$|(?-xism:(?:\.\.))$))/';
        foreach ($range as $item) {
            foreach (preg_split($sectsep, $item) as $section) {
                if (preg_match($rangesep,$section)) {
                    list ($start, $end) = preg_split($rangesep, $section, 2);
                    if ($start > $end) {
                      trigger_error("$start is > $end",E_NOTICE);
                      list ($start, $end) = array($end, $start);
                    }
                    if ($start == $end) {
                      trigger_error("$start:$end is pointless",E_NOTICE);
                      if ($type == "add") {
                        $this->_addnumbers($start);
                      }
                      elseif ($type == "del") {
                        $this->_delnumbers($start);
                      }
                      else {
                        trigger_error("Neither 'add' nor 'del' was passed initialize()",E_ERROR);
                      }
                    } else {
                        if ($type == "add") {
                          if(($end - $start) > $this->{max_hash_size}) {
                            $this->_addrange($start,$end);
                          } else {
                            $this->_addnumbers(range($start,$end));
                          }
                        } elseif ($type == "del") {
                          if($end - $start > $this->{max_hash_size}) {
                            $this->_delrange($start,$end);
                          } else {
                            $this->_delnumbers(range($start,$end));
                          }
                        } else {
                            trigger_error("Neither 'add' nor 'del' was passed initialize()",E_ERROR);
                        }
                    }
                } else {
                    if ($type == "add") {
                        $this->_addnumbers($section);
                    }
                    elseif ($type == "del") {
                        $this->_delnumbers($section);
                    } else {
                        trigger_error( "Neither 'add' nor 'del' was passed initialize()",E_ERROR);
                    }
                }
            }
        }
    }
    
    /**
     * Add a range thats stored as a pointer
     *
     * @param   integer   $start    Range start
     * @param   integer   $end      Range end
     * @return  void
     */
    private function _addrange($start,$end) {
      $this->{_largeRangehash}{"$start .. $end"} = array($start, $end);
    }
  
    /**
     * Remove a range thats stored as a pointer
     *
     * @param   integer   $start    Range start
     * @param   integer   $end      Range end
     * @return  void
     */  
    private function _delrange($start,$end) {
      unset($this->{_largeRangehash}{"$start .. $end"});
    }

    /**
     * Test to see if a value is in the large range hash
     *
     * @param   integer    $test   Value to test for
     * @return  boolean    True if its in any of the large ranges, otherwise false
     */
    public function _testlarge($test) {
        if(!isset($this->{_largeRangehash})) {
            return 0;
        }
        foreach($this->{_largeRangehash} as $rangeID=>$range) {
            if ($test >= $range[0]
                && $test <= $range[1]) {
                return 1;
            }
        }
        return 0;
    }
    
    /**
     * Add number to the range array
     *
     * @param   array       $numbers        Numbers to add
     * @return  void
     */
    private function _addnumbers($numbers) {
      if(is_numeric($numbers)) {
        $numbers = array($numbers);
      }      
      foreach ($numbers as $number) {
        $this->_rangehash[$number] = 1;
      }
    }

    /**
     * Remove numbers from the range array
     *
     * @param   array       $numbers        Numbers to remove
     * @return  void
     */    
    private function _delnumbers() {
      foreach ($numbers as $number) {
        unset($this->_rangehash[$number]);
      }
    }
    
    /**
     * Test if a number is in the range
     *
     * @param   mixed either a single number, an array or multiple numbers each
     * passed as their own parameter
     *
     * @example inrange(1)
     * Return true if the number is in the range, otherwise false
     * 
     * @example inrange(array(1,2))
     * Returns an array where each values results in the same array position
     * array(true,false)
     * 
     * @example inrange(1,2,3)
     * Returns true if all numbers are in the range, otherwise false
     * 
     * @return  mixed (true||false for single elements or array of true/false results for each input)
     */     
    public function inrange() {
        $args = func_get_args();
        if (sizeof($args) == 1) {
          if ( !empty($this->_rangehash[$args[0]]) 
              || $this->_testlarge($args[0])) {
              return 1;
          } else {
              return 0;
          }
        } else {
          if (is_array($args)) {
            $returncodes;
            foreach ($args as $test) {
              array_push($returncodes, ($this->inrange($test)) ? true : false);
            }
            return $returncodes;
          } else {
            foreach ($args as $test) {
              if (!$this->inrange($test)) {
                return true;
              }
              return false;
            }
          }
        }
    }
    
    /**
     * Add a value to the range
     *
     * @param   $range      Range string
     * @return  void
     */
    public function addrange($range) {
      $this->initialize("add", $range);
    }
    
    /**
     * Remove a value from the range
     * 
     * @param   $range      Range string
     * @return  void
     */
    public function delrange($range) {
      $this->initialize("del", $range);
    }
    
    /**
     * Returns the range as a scalar value or as an array
     *
     * @param   boolean     $wantArray      If true the returns the range as an array
     * @return  mixed       Range as a string or as an array
     */
    public function range($wantArray = false) {
      if ($wantArray) {
        $range = array_keys($this->_rangehash);
        if(isset($this->{_largeRangehash})) {
            foreach($this->{_largeRangehash} as $rangeID=>$range) {
                if ( $range[0] > PHP_INT_MAX 
                    || $range[1] > PHP_INT_MAX 
                    || ( $range[1] -  @$range[0]) > PHP_INT_MAX  ) {
                    trigger_error("Range to large to return", E_NOTICE);
                    return 0;
                }
                $range = array_merge($range, range($range[0],$range[1]));
            }
        }       
        sort($range);
        return $range;
      } else {
        $range    = $this->range(true);
        $previous = array_shift($range);
        $format   = "$previous";
        foreach ($range as $current) {
          if ($current == ($previous + 1)) {
            $format  = preg_replace("/\.\.$previous$/",'', $format);
            $format .= "..$current"; 
          } else {
            $format .= ",$current";
          }
          $previous = $current;
        }
        $negated = null;
        if($this->negated()) {
          $negated = '!';
        }
        return $negated.$format;
      }
    }
    
    /**
     * Returns the size of the range
     */
    public function size() {
      $size = sizeof($this->range(true));
      if(isset($this->{_largeRangehash})) {
        foreach($this->{_largeRangehash} as $rangeID=>$range) {
          $size += ($range[1] - $range[0]) + 1;
        }
      }  
      return $size;
    }
    
    /**
     * Returns true if this is a negated range
     */
    public function negated() {
      return $this->negatedRange;
    }
    
    /**
     * Convert range formats "1-10" to "1..10"
     *
     * @param string  $range  Range to clean up that uses the format "#-#"
     * @return  string  Range cleaned up as "#..#"
     */
    public function cleanDashedRange($range) {
      $range = preg_replace('/(\d+)-(\d+)/','\1..\2',$range);
      return $range;
    }    
}
?>
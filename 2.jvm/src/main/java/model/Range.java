package model;

import java.util.HashMap;
import java.util.HashSet;
import java.util.Map;
import java.util.Set;

public class Range {
    private int rangeId;
    private long minValue;
    private long maxValue;
    /**
     * Count of free numbers for allocation.
     */
    private long free;
    /**
     * Information about shuffle of numbers.
     */
    private Map<Long, Long> numbersByPosition = new HashMap<>();
    /**
     * Set of shuffled numbers for fast determining number state.
     */
    private Set<Long> shuffledNumbers = new HashSet<>();

    /**
     * Range constructor.
     *
     * @param rangeId  Range id.
     * @param minValue Left edge of range.
     * @param maxValue Right edge of range.
     */
    public Range(int rangeId, long minValue, long maxValue) {
        this.rangeId = rangeId;
        this.minValue = minValue;
        this.maxValue = maxValue;
        this.free = maxValue - minValue + 1;
    }

    /**
     * Allocate free number from range.
     *
     * @return number.
     */
    synchronized public long allocate() {
        long randPosition = (long) Math.floor(Math.random() * free);
        long number = randPosition;

        if (numbersByPosition.containsKey(randPosition)) {
            number = numbersByPosition.get(randPosition);
        }
        long numberToSubstitute = numbersByPosition.getOrDefault(free-1, free-1);
        shuffledNumbers.add(numberToSubstitute);
        numbersByPosition.put(randPosition, numberToSubstitute);
        numbersByPosition.remove(free-1);
        free--;
        shuffledNumbers.remove(number);

        return number + minValue;
    }

    /**
     * Release number - return it to pool of free numbers.
     *
     * @param number Number, that was allocated before.
     */
    synchronized public void release(long number) {
        if (number < minValue || number > maxValue) {
            throw new RuntimeException("Incorrect number " + number);
        }
        if ((number < minValue + free && !numbersByPosition.containsKey(number)) ||
            shuffledNumbers.contains(number)
        ) {
            throw new RuntimeException("Cannot release free number");
        }

        // Because count from 0 for usage in map and set.
        long internalNumber = number - minValue;

        if (internalNumber < free) {
            // To save place put number to its place, removing record with key=number.
            long shuffledNumber = numbersByPosition.get(internalNumber);
            numbersByPosition.remove(internalNumber);
            if (shuffledNumber != free) {
                numbersByPosition.put(free, shuffledNumber);
            } else {
                shuffledNumbers.remove(free);
            }
        } else if (internalNumber > free) {
            // Place number to end of list.
            numbersByPosition.put(free, internalNumber);
            shuffledNumbers.add(internalNumber);
        }
        free++;
    }

}

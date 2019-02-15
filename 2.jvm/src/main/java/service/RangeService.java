package service;

import model.Range;

import java.util.ArrayList;

public class RangeService {
    private ArrayList<Range> rangePool = new ArrayList<>();

    /**
     * Create new range and return its id.
     *
     * @param minValue Left edge of range.
     * @param maxValue Right edge of range.
     * @return index.
     */
    public int createRange(long minValue, long maxValue) {
        int index;
        synchronized (rangePool) {
            index = rangePool.size();
            rangePool.add(index, new Range(index, minValue, maxValue));
        }
        return index;
    }

    /**
     * Allocate number from range.
     *
     * @param rangeId id of range.
     * @return free number from range.
     */
    public long allocate(int rangeId) throws Exception {
        return getRange(rangeId).allocate();
    }

    /**
     * Release number from range.
     *
     * @param rangeId index of range.
     * @param number number to release.
     */
    public void release(int rangeId, long number) throws Exception {
        getRange(rangeId).release(number);
    }

    /**
     * Get range by its id, check if it's not correct.
     *
     * @param rangeId range index.
     * @return range model object.
     */
    private Range getRange(int rangeId) throws Exception {
        Range range = rangePool.get(rangeId);
        if (range == null) {
            throw new Exception("Incorrect range id");
        }
        return range;
    }
}

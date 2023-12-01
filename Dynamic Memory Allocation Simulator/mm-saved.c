/*
 ******************************************************************************
 *                               mm.c                                         *
 *           64-bit struct-based implicit free list memory allocator          *
 *                      without coalesce functionality                        *
 *                 CSE 361: Introduction to Computer Systems                  *
 *                                                                            *
 *  ************************************************************************  *
 *                           Lingchen Meng                                    *
 *                                                                            *
 *  ************************************************************************  *
 */


#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <stdbool.h>
#include <stddef.h>
#include <assert.h>
#include <stddef.h>

#include "mm.h"
#include "memlib.h"

#ifdef DRIVER
/* create aliases for driver tests */
#define malloc mm_malloc
#define free mm_free
#define realloc mm_realloc
#define calloc mm_calloc
#endif /* def DRIVER */


/*
 * If DEBUG is defined, enable printing on dbg_printf and contracts.
 * Debugging macros, with names beginning "dbg_" are allowed.
 * You may not define any other macros having arguments.
 */
// #define DEBUG // uncomment this line to enable debugging

#ifdef DEBUG
/* When debugging is enabled, these form aliases to useful functions */
#define dbg_printf(...) printf(__VA_ARGS__)
#define dbg_requires(...) assert(__VA_ARGS__)
#define dbg_assert(...) assert(__VA_ARGS__)
#define dbg_ensures(...) assert(__VA_ARGS__)
#else
/* When debugging is disnabled, no code gets generated for these */
#define dbg_printf(...)
#define dbg_requires(...)
#define dbg_assert(...)
#define dbg_ensures(...)
#endif

/* Basic constants */
typedef uint64_t word_t;
static const size_t wsize = sizeof(word_t);   // word and header size (bytes)
static const size_t dsize = 2*sizeof(word_t);       // double word size (bytes)
static const size_t min_block_size = 4*sizeof(word_t); // Minimum block size
static const size_t chunksize = (1 << 12);    // requires (chunksize % 16 == 0)
#define N 7  //N-fit

static const word_t alloc_mask = 0x1;
static const word_t size_mask = ~(word_t)0xF;

typedef struct block
{
    /* Header contains size + allocation flag */
    word_t header;
    /*
     * We don't know how big the payload will be.  Declaring it as an
     * array of size 0 allows computing its starting address using
     * pointer notation.
     */
    union {
        struct {
            struct block *prev;
            struct block *next;
        };
        char payload[0];
    };
    /*
     * We can't declare the footer as part of the struct, since its starting
     * position is unknown
     */
} block_t;


/* Global variables */
/* Pointer to first block */
static block_t *heap_start = NULL;
static block_t *free_list = NULL;


bool mm_checkheap(int lineno);

/* Function prototypes for internal helper routines */
static block_t *extend_heap(size_t size);
static void place(block_t *block, size_t asize);
static block_t *find_fit(size_t asize);
static block_t *coalesce(block_t *block);

static size_t max(size_t x, size_t y);
static size_t round_up(size_t size, size_t n);
static word_t pack(size_t size, bool alloc);

static size_t extract_size(word_t header);
static size_t get_size(block_t *block);
static size_t get_payload_size(block_t *block);

static bool extract_alloc(word_t header);
static bool get_alloc(block_t *block);

static void write_header(block_t *block, size_t size, bool alloc);
static void write_footer(block_t *block, size_t size, bool alloc);

static block_t *payload_to_header(void *bp);
static void *header_to_payload(block_t *block);

static block_t *find_next(block_t *block);
static word_t *find_prev_footer(block_t *block);
static block_t *find_prev(block_t *block);

static void remove_free(block_t *block);
static void insert_free(block_t *block);

static void remove_free(block_t *block) {
    block_t *next = block->next;
    block_t *prev = block->prev;

    if (prev) {
        prev->next = block->next;
    } else {
        free_list = block->next;
    }
    if (next != NULL){
        next->prev = prev;
    }
}

static void insert_free(block_t *block) {
    block->next = free_list;
    if (free_list != NULL) {
        free_list->prev = block;
    }
    block->prev = NULL;
    free_list = block;
}

/*
 * Initializes the memory manager by creating an initial empty heap,
 * setting up the prologue and epilogue headers and footers, and extending
 * the empty heap with 16*dsize.
 * Returns true if initialization is successful, false otherwise.
 */

bool mm_init(void)
{
    // Create the initial empty heap
    word_t *start = (word_t *)(mem_sbrk(6*wsize));

    if (start == (void *)-1)
    {
        return false;
    }

    start[0] = pack(0, false); // Prologue footer
    start[1] = pack(2*dsize, true); // Epilogue header
    start[2] = pack(0, false);
    start[3] = pack(0, false);
    start[4] = pack(2*dsize, true);
    start[5] = pack(0, false);

    // Heap starts with first block header (epilogue)
    heap_start = (block_t *)&(start[1]);
    free_list = NULL;

    // Extend the empty heap with a free block of chunksize bytes
    if (extend_heap(16*dsize) == NULL)
    {
        return false;
    }
    return true;
}


/*
 * Allocates a block of memory of the given size using the explicit free list
 * allocator. Adjusts the block size to include overhead and meet alignment requirements.
 * Searches the free list for a suitable free block. If no fit is found, requests
 * more memory through extend_heap and places the block. Returns a pointer to the
 * allocated memory, or NULL if allocation is unsuccessful.
 */
void *malloc(size_t size)
{
    dbg_requires(mm_checkheap(__LINE__));
    size_t asize;      // Adjusted block size
    size_t extendsize; // Amount to extend heap if no fit is found
    block_t *block;
    void *bp = NULL;

    if (heap_start == NULL) // Initialize heap if it isn't initialized
    {
        mm_init();
    }

    if (size == 0) // Ignore spurious request
    {
        dbg_ensures(mm_checkheap(__LINE__));
        return bp;
    }

    // Adjust block size to include overhead and to meet alignment requirements
    asize = round_up(size + dsize, dsize);

    // Search the free list for a fit
    block = find_fit(asize);

    // If no fit is found, request more memory, and then and place the block
    if (block == NULL)
    {
        extendsize = max(asize, chunksize);
        block = extend_heap(extendsize);
        if (block == NULL) // extend_heap returns an error
        {
            return bp;
        }

    }

    place(block, asize);
    bp = header_to_payload(block);

    dbg_ensures(mm_checkheap(__LINE__));
    return bp;
}

/*
 * Deallocates the memory block pointed to by 'bp' by updating its header and footer
 * to mark it as free. Then, coalesces adjacent free blocks to merge them into a
 * larger free block. If 'bp' is NULL, the function does nothing.
 */
void free(void *bp)
{
    if (bp == NULL)
    {
        return;
    }

    block_t *block = payload_to_header(bp);
    size_t size = get_size(block);

    write_header(block, size, false);
    write_footer(block, size, false);

    coalesce(block);

}

/*
 * Changes the size of the memory block pointed to by 'ptr' to 'size' bytes.
 * If 'ptr' is NULL, it is equivalent to malloc(size). If 'size' is 0, the
 * block is freed, and NULL is returned. Otherwise, realloc allocates a new
 * block of the requested size, copies the old data to the new block, frees
 * the old block, and returns a pointer to the new block. If reallocation
 * fails, the original block is left untouched, and NULL is returned.
 */
void *realloc(void *ptr, size_t size)
{
    block_t *block = payload_to_header(ptr);
    size_t copysize;
    void *newptr;

    // If size == 0, then free block and return NULL
    if (size == 0)
    {
        free(ptr);
        return NULL;
    }

    // If ptr is NULL, then equivalent to malloc
    if (ptr == NULL)
    {
        return malloc(size);
    }

    // Otherwise, proceed with reallocation
    newptr = malloc(size);
    // If malloc fails, the original block is left untouched
    if (newptr == NULL)
    {
        return NULL;
    }

    // Copy the old data
    copysize = get_payload_size(block); // gets size of old payload
    if(size < copysize)
    {
        copysize = size;
    }
    memcpy(newptr, ptr, copysize);

    // Free the old block
    free(ptr);

    return newptr;
}

/*
 * Allocates a block of memory for an array of 'elements' each of size 'size'.
 * The total allocated space is 'elements * size'. If the multiplication of
 * 'elements' and 'size' would result in overflow, returns NULL. Otherwise,
 * allocates memory using malloc and initializes all bits to 0 using memset.
 * Returns a pointer to the allocated memory, or NULL if allocation is unsuccessful.
 */
void *calloc(size_t elements, size_t size)
{
    void *bp;
    size_t asize = elements * size;

    if (asize/elements != size)
    {
        // Multiplication overflowed
        return NULL;
    }

    bp = malloc(asize);
    if (bp == NULL)
    {
        return NULL;
    }
    // Initialize all bits to 0
    memset(bp, 0, asize);

    return bp;
}

/*
 * Extends the heap by allocating a new block of memory with a size
 * at least as large as the specified 'size'. Ensures the new block is
 * aligned properly and initializes its header, footer, and the epilogue
 * header. If the previous block in memory is free, coalesces the new block
 * with the free block. Returns a pointer to the coalesced block.
 */
static block_t *extend_heap(size_t size)
{
    void *bp;

    // Allocate an even number of words to maintain alignment
    size = round_up(size, dsize);
    if ((bp = mem_sbrk(size)) == (void *)-1)
    {
        return NULL;
    }

    // Initialize free block header/footer
    block_t *block = payload_to_header(bp);
    write_header(block, size, false);
    write_footer(block, size, false);
    // Create new epilogue header
    block_t *block_next = find_next(block);
    write_header(block_next, 0, true);

    // Coalesce in case the previous block was free
    return coalesce(block);
}

/*
 * Combines the free block pointed to by 'block' with any adjacent free blocks
 * in the heap. Checks the status of the previous and next blocks to determine
 * whether coalescing is possible. If coalescing occurs, updates the header and
 * footer of the combined block and removes any coalesced blocks from the free list.
 * Finally, inserts the coalesced block into the free list. Returns a pointer to
 * the coalesced block.
 */
static block_t *coalesce(block_t * block)
{
    block_t *next = find_next(block);
    block_t *prev = find_prev(block);
    bool next_alloc = get_alloc(next);
    bool prev_alloc = get_alloc(prev);
    size_t new_size = get_size(block);

    if (prev_alloc && !next_alloc){
        new_size += get_size(next);
        remove_free(next);
        write_header(block, new_size, false);
        write_footer(block, new_size, false);
    }
    else if (!prev_alloc && next_alloc){
        new_size += get_size(prev);
        block = prev;
        remove_free(block);
        write_header(block, new_size, false);
        write_footer(block, new_size, false);
    }
    else if (!prev_alloc && !next_alloc){
        new_size += get_size(prev);
        new_size += get_size(next);
        remove_free(prev);
        remove_free(next);
        block = prev;
        write_header(block, new_size, false);
        write_footer(block, new_size, false);
    }
    insert_free(block);
    dbg_printf("end coalesce");
    return block;
}

/*
 * Places an allocated block of size 'asize' within the free block 'block'.
 * If the remaining space after allocation is large enough to create a new free
 * block ('min_block_size' or larger), splits the block and updates the headers
 * and footers of the allocated and free blocks. Removes the allocated block from
 * the free list. If the remaining space is not sufficient for a new free block,
 * allocates the entire free block and removes it from the free list. The function
 * then coalesces the remaining free block, if any. 
 */
static void place(block_t *block, size_t asize)
{
    size_t csize = get_size(block);

    if ((csize - asize) >= min_block_size)
    {
        block_t *block_next;
        write_header(block, asize, true);
        write_footer(block, asize, true);
        remove_free(block);

        block_next = find_next(block);
        write_header(block_next, csize-asize, false);
        write_footer(block_next, csize-asize, false);
        coalesce(block_next);
    }

    else
    {
        write_header(block, csize, true);
        write_footer(block, csize, true);
        remove_free(block);
    }
}

/*
 * Finds a free block in the free list that is large enough to accommodate
 * an allocated block of size 'asize'. Implements an N-th fit strategy, where N
 * represents the number of candidates to consider. Iterates through the free
 * list and stores the first N suitable blocks in the array 'N_fit'. Then, selects
 * the block with the smallest size from the candidates. Returns a pointer to the
 * selected block or NULL if no fit is found.
 */
static block_t *find_fit(size_t asize)
{
    //implement N-th fit
    block_t *N_fit[N] = { NULL };
    block_t *block;
    unsigned int m = 0;
    for (block = free_list; block != NULL;
         block = block->next)
    {
        if (!(get_alloc(block)) && (asize <= get_size(block)))
        {
            if (m < N){
                N_fit[m] = block;
                m ++;
            }
            else{
                break;
            }
        }
    }
    block = N_fit[0];
    unsigned int i;
    for (i = 1; i < N; i++){
        if (block != NULL && N_fit[i] != NULL) {
            if (get_size(block) > get_size(N_fit[i])) {
                block = N_fit[i];
            }
        }else{
            break;
        }
    }
    return block; // no fit found
}

/*
 */
bool mm_checkheap(int line)
{
    block_t *block;
    int count = 0;
    for (block = free_list; block != NULL;
         block = block->next)
    {
        count += 1;
    }
    printf("%d\n", count);
    return true;
}

/*
 * max: returns x if x > y, and y otherwise.
 */
static size_t max(size_t x, size_t y)
{
    return (x > y) ? x : y;
}

/*
 * round_up: Rounds size up to next multiple of n
 */
static size_t round_up(size_t size, size_t n)
{
    return (n * ((size + (n-1)) / n));
}

/*
 * pack: returns a header reflecting a specified size and its alloc status.
 *       If the block is allocated, the lowest bit is set to 1, and 0 otherwise.
 */
static word_t pack(size_t size, bool alloc)
{
    return alloc ? (size | alloc_mask) : size;
}


/*
 * extract_size: returns the size of a given header value based on the header
 *               specification above.
 */
static size_t extract_size(word_t word)
{
    return (word & size_mask);
}

/*
 * get_size: returns the size of a given block by clearing the lowest 4 bits
 *           (as the heap is 16-byte aligned).
 */
static size_t get_size(block_t *block)
{
    return extract_size(block->header);
}

/*
 * get_payload_size: returns the payload size of a given block, equal to
 *                   the entire block size minus the header and footer sizes.
 */
static word_t get_payload_size(block_t *block)
{
    size_t asize = get_size(block);
    return asize - dsize;
}

/*
 * extract_alloc: returns the allocation status of a given header value based
 *                on the header specification above.
 */
static bool extract_alloc(word_t word)
{
    return (bool)(word & alloc_mask);
}

/*
 * get_alloc: returns true when the block is allocated based on the
 *            block header's lowest bit, and false otherwise.
 */
static bool get_alloc(block_t *block)
{
    return extract_alloc(block->header);
}

/*
 * write_header: given a block and its size and allocation status,
 *               writes an appropriate value to the block header.
 */
static void write_header(block_t *block, size_t size, bool alloc)
{
    block->header = pack(size, alloc);
}


/*
 * write_footer: given a block and its size and allocation status,
 *               writes an appropriate value to the block footer by first
 *               computing the position of the footer.
 */
static void write_footer(block_t *block, size_t size, bool alloc)
{
    word_t *footerp = (word_t *)((block->payload) + get_size(block) - dsize);
    *footerp = pack(size, alloc);
}


/*
 * find_next: returns the next consecutive block on the heap by adding the
 *            size of the block.
 */
static block_t *find_next(block_t *block)
{
    dbg_requires(block != NULL);
    block_t *block_next = (block_t *)(((char *)block) + get_size(block));

    dbg_ensures(block_next != NULL);
    return block_next;
}

/*
 * find_prev_footer: returns the footer of the previous block.
 */
static word_t *find_prev_footer(block_t *block)
{
    // Compute previous footer position as one word before the header
    return (&(block->header)) - 1;
}

/*
 * find_prev: returns the previous block position by checking the previous
 *            block's footer and calculating the start of the previous block
 *            based on its size.
 */
static block_t *find_prev(block_t *block)
{
    word_t *footerp = find_prev_footer(block);
    size_t size = extract_size(*footerp);
    return (block_t *)((char *)block - size);
}

/*
 * payload_to_header: given a payload pointer, returns a pointer to the
 *                    corresponding block.
 */
static block_t *payload_to_header(void *bp)
{
    return (block_t *)(((char *)bp) - offsetof(block_t, payload));
}

/*
 * header_to_payload: given a block pointer, returns a pointer to the
 *                    corresponding payload.
 */
static void *header_to_payload(block_t *block)
{
    return (void *)(block->payload);
}
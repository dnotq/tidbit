//
// Hunter-Killer Maze Algorithm
// TI-99/4A Extended BASIC Version
// Matthew Hagerty
// Public Domain
//
// Requires TidBit: https://dnotq.io/tidbit/tidbit.html
//
// Example *INPUT* program for the TidBit translator.  The code could be better
// for sure, and a different algorithm should probably be used.
//

// Initialize
CALL CLEAR :: OPTION BASE 0 :: RANDOMIZE
CALL CHAR(96,"0000000000000000")
CALL CHAR(97,"0101010101010101")
CALL CHAR(98,"00000000000000FF")
CALL CHAR(99,"01010101010101FF")
CALL CHAR(100,"017D4545457D01FF")
CALL COLOR(9,2,11)

// Initialize the maze screen offset and starting "hunt" mode coordinate.
OX=10 :: OY=6 :: HX=1 :: HY=1

// Dimension the MAZE array and initialize the border.
SX=12 :: SY=12 ::
DIM MAZE(13,13)

FOR I=0 TO SX+1 ::
    MAZE(I,0)=-1 ::
    MAZE(I,SY+1)=-1 ::
NEXT I

FOR I=1 TO SY ::
    MAZE(0,I)=-1 ::
    MAZE(SX+1,I)=-1 ::
NEXT I

// Dimension and initialize the direction-to-offset lookup table.  What
// this does is saves us from having to use IF statements to find out
// what to add to X and Y for a given direction D.
DIM P(4,2)
    P(1,1)=0 ::
    P(1,2)=-1 ::
    P(2,1)=1 ::
    P(2,2)=0
    P(3,1)=0 ::
    P(3,2)=1 ::
    P(4,1)=-1 ::
    P(4,2)=0

DIM DBIT(4) ::
    DBIT(1)=1 ::
    DBIT(2)=2 ::
    DBIT(3)=4 ::
    DBIT(4)=8

DIM DINV(4) ::
    DINV(1)=4 ::
    DINV(2)=8 ::
    DINV(3)=1 ::
    DINV(4)=2

// Draw the initial maze, all walls up.
FOR I=OY TO OY+SY-1
    CALL HCHAR(I,OX,99,SX)
NEXT I

// Pick a random starting point and enter Kill mode.
KillMode:

    X=INT(RND*SX)+1 ::
    Y=INT(RND*SY)+1 ::
    IF MAZE(X,Y)<>0 THEN KillMode

    DISPLAY AT(1,1):"KILL"

    // Pick a random direction D (1-4) and remember it (D1) so we know when we
    // have tried all 4 directions for this cell.  RND is a very expensive
    // instruction, as is the multiplication.
    PickRandomDirection:

        D=INT(RND*3)+1 ::
        D1=D

        CheckNewCell:
            // Generate a new cell location (NX,NY) and see if it is okay to move there.
            NX=X+P(D,1) ::
            NY=Y+P(D,2)

            IF MAZE(NX,NY)<>0 THEN InvalidDirection

            // Drop the wall in the current cell for the direction in which we are
            // leaving the cell.  Then display the proper wall character for this cell.
            MAZE(X,Y)=MAZE(X,Y) OR DBIT(D)
            C=99

            IF (MAZE(X,Y) AND 2)=2 THEN C=98

            IF (MAZE(X,Y) AND 4)=4 THEN
                IF C=98 THEN
                    C=96
                ELSE
                    C=97
				ENDIF
			ENDIF

            CALL HCHAR(OY+Y-1,OX+X-1,C)

            // Set X and Y to the new cell and drop the wall for the direction in which
            // we just entered the cell.
            X=NX ::
            Y=NY ::
            MAZE(X,Y)=DINV(D)

    GOTO PickRandomDirection

    InvalidDirection:

        D=D+1 ::
        IF D>4 THEN D=1

    IF D<>D1 THEN CheckNewCell

// Begin "hunt" mode.  Displays the proper character for the current cell
// and marks the cell as "complete".
HuntMode:

    C=99
    IF (MAZE(X,Y) AND 2)=2 THEN C=98

    IF (MAZE(X,Y) AND 4)=4 THEN
        IF C=98 THEN
            C=96
        ELSE
            C=97
		ENDIF
	ENDIF

    CALL HCHAR(OY+Y-1,OX+X-1,C)
    MAZE(X,Y)=MAZE(X,Y) OR 16

    // Set up the hunt loops.  Hunting is a sequential scan over the maze,
    // looking for a cell that has been visited but is not yet complete.
    // Once the whole maze has been scanned in hunt mode and all cells are
    // found to be "complete", the maze is done.

    RESET=0

    FOR J=HY TO SY ::
        DISPLAY AT(1,1):"HUNT: ";J

        FOR I=HX TO SX

            // Looking for a visited cell to being Kill mode again.  All cells that
            // are either 0 or >=16 are unvisited or complete, and are skipped.

            // If an unvisited cell (0) is found, the next time we enter hunting mode,
            // we must begin at the beginning (indicated by the RESET variable.)
            // However, if there are no unvisited cells up to the next cell to
            // continue with, we can pick up hunting were we left off.

            IF MAZE(I,J)=0 THEN
                RESET=1 ::
                GOTO Next_I
			ENDIF

            IF MAZE(I,J)>=16 THEN Next_I

            // A valid cell was found, so set that as the current cell.  The direction
            // is set instead of chosen again at random since 1. this cell already had
            // a random direction chosen for it once before, and 2. choosing a random
            // number is expensive and we want to avoid it if possible.  This is why
            // we jump back to the line just following the random direction generation.

            X=I :: Y=J

            IF RESET=0 THEN
                HX=I ::
                HY=J
            ELSE
                HX=1 ::
                HY=1
			ENDIF

            DISPLAY AT(1,1):"KILL"
            D=4 ::
            D1=4

            // Hmm, jumping out of a FOR - NEXT loop is not really good
            // programming practice.
            GOTO CheckNewCell

        // Hunting loop iterators.  If we picked up hunting where we had left off
        // on a previous hunting loop, the hunting column (HX) must be reset back
        // to 1, otherwise hunting would miss chunks of the maze if we hunt past
        // the current line during this hunting iteration.
        Next_I:
        NEXT I

        HX=1

    NEXT J

DISPLAY AT(1,1):"DONE"
KeyWait: CALL KEY(0,K,S) :: IF S=0 THEN KeyWait

END
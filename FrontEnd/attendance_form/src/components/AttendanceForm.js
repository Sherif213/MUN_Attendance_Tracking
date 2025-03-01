import { useEffect, useState, useRef } from "react";

import {
  TextField,
  Button,
  Container,
  Typography,
  Grid,
  Card,
  CardContent,
  Divider,
  Box,
  Chip
} from "@mui/material";
import { CheckCircle, Cancel } from "@mui/icons-material";
import { styled, keyframes } from "@mui/system";

const fadeIn = keyframes`
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
`;

const StyledCard = styled(Card)(({ theme }) => ({
  animation: `${fadeIn} 0.5s ease-in-out`,
  borderRadius: "16px",
  boxShadow: "0 8px 32px rgba(0, 0, 0, 0.1)",
  background: "linear-gradient(145deg, #ffffff, #f0f0f0)",
  backdropFilter: "blur(10px)",
  border: "1px solid rgba(255, 255, 255, 0.3)",
  overflow: "hidden",
}));

const StyledButton = styled(Button)(({ theme }) => ({
  borderRadius: "12px",
  padding: "12px 24px",
  fontWeight: "bold",
  transition: "all 0.3s ease",
  "&:hover": {
    transform: "translateY(-2px)",
    boxShadow: "0 4px 16px rgba(0, 0, 0, 0.2)",
  },
}));

const ClockContainer = styled(Box)(({ theme }) => ({
  display: "flex",
  flexDirection: "column",
  alignItems: "center",
  justifyContent: "center",
  marginBottom: "20px",
}));

const DigitalTime = styled(Typography)(({ theme }) => ({
  fontSize: "1.5rem",
  fontWeight: "bold",
  color: "#1976D2",
  marginTop: "10px",
}));

const AttendanceForm = () => {
  const [formData, setFormData] = useState({ id: "", code: "" });
  const [errors, setErrors] = useState({});
  const [attendance, setAttendance] = useState({
    session1: false,
    session2: false,
    session3: false,

  });
  const [submitted, setSubmitted] = useState(false);
  const [currentTime, setCurrentTime] = useState(new Date());
  const canvasRef = useRef(null);



  const formatTime = (date) => {
    return date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit", second: "2-digit" });
  };

  return (
    <Container maxWidth="md" sx={{ display: "flex", flexDirection: "column", alignItems: "center", mt: 5 }}>
      {/* Modern Clock */}
      <ClockContainer>
        <DigitalTime>{formatTime(currentTime)}</DigitalTime>
      </ClockContainer>

      <StyledCard sx={{ p: 3, width: "100%", maxWidth: 600 }}>
      <CardContent>
          <Typography variant="h4" gutterBottom align="center" sx={{ fontWeight: "bold", color: "#1976D2" }}>Attendance Form</Typography>
          <Divider sx={{ my: 3 }} />
          <Box sx={{ display: "flex", justifyContent: "center", flexWrap: "wrap", gap: 1, mb: 3 }}>
            {Object.keys(attendance).map((session, index) => (
              <Chip
                key={index}
                label={session.replace("session", "Session ")}
                icon={attendance[session] ? <CheckCircle color="success" /> : <Cancel color="error" />}
                sx={{ fontSize: "1rem", fontWeight: "bold", padding: "10px 15px", borderRadius: "8px", boxShadow: "0px 4px 10px rgba(0,0,0,0.1)" }}
                color={attendance[session] ? "success" : "error"}
              />
            ))}
          </Box>
          <form>
            <TextField fullWidth label="ID" name="id" value={formData.id} onChange={(e) => setFormData({ ...formData, id: e.target.value })} margin="normal" variant="outlined" />
            <TextField fullWidth label="Code on Board" name="code" value={formData.code} onChange={(e) => setFormData({ ...formData, code: e.target.value })} margin="normal" variant="outlined" />
            <Button type="submit" variant="contained" color="primary" fullWidth sx={{ mt: 2, py: 1.5, fontSize: "1rem" }}>Submit Attendance</Button>
          </form>
        </CardContent>
      </StyledCard>
    </Container>
  );
};

export default AttendanceForm;